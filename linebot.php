<?php
require "scorekeisan.php";
//require "recordscore.php";

$accessToken = 'M12Yguz2fW3gq0AYBLk2m49F8VcL8HocX7Q+F5RM9zlHxfNns/mhFZvZKh77HAhvrT9RHuNORApTXUzr67gQhtq6FWl8GyD6oZFruqus8SM8xgumE1lvBHG5A2vEhItq5MYUX5//QEu4kXP3WVnKpQdB04t89/1O/w1cDnyilFU=';
 
//ユーザーからのメッセージ取得
$json_string = file_get_contents('php://input');
$json_object = json_decode($json_string);

//DB接続用パラメーター
$DB_SERVER = getenv('DB_HOST');
$Port = "5432";
$DB_NAME = getenv('DB_DATABASE');
$DB_UID = getenv('DB_USERNAME');
$DB_PASS = getenv('DB_PASSWORD');

define("DB_CONECT","host=$DB_SERVER port=$Port dbname=$DB_NAME user=$DB_UID password=$DB_PASS");


//取得データ
$replyToken = $json_object->{"events"}[0]->{"replyToken"};        //返信用トークン
$message_type = $json_object->{"events"}[0]->{"message"}->{"type"};    //メッセージタイプ
$message_text = $json_object->{"events"}[0]->{"message"}->{"text"};    //メッセージ内容

$date_s=(string)date("Ymd");

//メッセージタイプが「text」以外のときは何も返さず終了
if($message_type != "text") exit;

if(strpos($message_text,'確認') !== false){
//  if(preg_match('/^([確認]+)/',$message_text)) {
  //messageの先頭に'確認'が含まれている場合

	$return_message_text = "現在の結果だよ！";
//	$return_message_text = record_score();

	$sqlcmd="SELECT player, Sum(scoringpoints) As pt,Sum(umapoints) As uma,Sum(totalpoints) As total FROM mjtable WHERE date='".$date_s."' GROUP BY player order by total desc;";

		//DB接続
		// 各種パラメータを指定して接続
			$pg_conn = pg_connect(DB_CONECT);

			if( $pg_conn ) {
				$db_message = "接続に成功しました";

				// SQLクエリ実行 終了ゲーム数
				$sqlhndno ="SELECT MAX(handnumber) FROM mjtable WHERE date='".$date_s."';";
				$resHandnumber = pg_query( $pg_conn, $sqlhndno);

				$val = pg_fetch_result($resHandnumber, 0, 0);

				// SQLクエリ実行
				$res = pg_query( $pg_conn, $sqlcmd);
				//var_dump($res);

			$resultScore ="";
			for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
			    $rows = pg_fetch_array($res, NULL, PGSQL_ASSOC);
			    $resultScore=$resultScore.$rows['player']."\t".$rows['pt']."\t".$rows['uma']."\t".$rows['total']."\n";
			}

				$db_message = "クエリ実行できました";
				
			} else {
				$db_message = "クエリ実行できまませんでした";
			}

			// データベースとの接続を切断
			pg_close($pg_conn);

	$return_message_text=$return_message_text."\n\n".$val."回戦終了時点トータル\n".$resultScore;

} elseif(preg_match('/^([履歴]+)/',$message_text)) {
	//messageの先頭に履歴が含まれている場合


		//DB接続
		// 各種パラメータを指定して接続
			$pg_conn = pg_connect(DB_CONECT);

			if( $pg_conn ) {
				$db_message = "接続に成功しました";

				$sqlPlayer="select player from mjtable where date='".$date_s."' group by player order by player desc;";

				//参加者名の取得
				$playerToday = array();
				$resPlayer = pg_query( $pg_conn, $sqlPlayer);
				for ($i = 0 ; $i < pg_num_rows($resPlayer) ; $i++){
				    $rows = pg_fetch_array($resPlayer, NULL,PGSQL_NUM );
				$playerToday[$i]=$rows[0];
				//$return_message_text=$return_message_text.$playerToday[$i];
				}
				
				$sqlrollup = "select handnumber,sum(case player when '".$playerToday[0]."' then totalpoints else 0 end) ,sum(case player when '".$playerToday[1]."' then totalpoints else 0 end) ,sum(case player when '".$playerToday[2]."' then totalpoints else 0 end) ,sum(case player when '".$playerToday[3]."' then totalpoints else 0 end) from mjtable group by rollup(handnumber) order by handnumber asc;";

				// SQLクエリ実行
				$res = pg_query( $pg_conn, $sqlrollup);
				//var_dump($res);

			$resultScore ="";
			for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
			    $rows = pg_fetch_array($res, NULL,PGSQL_NUM );
			    $resultScore=$resultScore.str_pad($rows[0], 4, " ", STR_PAD_LEFT).str_pad($rows[1], 5, " ", STR_PAD_LEFT).str_pad($rows[2], 5, " ", STR_PAD_LEFT).str_pad($rows[3], 5, " ", STR_PAD_LEFT).str_pad($rows[4], 5, " ", STR_PAD_LEFT)."\n";
			}

				$db_message = "クエリ実行できました";
				
			} else {
				$db_message = "クエリ実行できまませんでした";
			}

			// データベースとの接続を切断
			pg_close($pg_conn);

	$return_message_text=$return_message_text."今日のゲームの履歴です"."\n".$resultScore;

} else {
  //messageの先頭に'確認'が含まれていない場合

	$return_message_text = return_score($message_text);
//	$return_message_text = "どや";
}

//返信実行
sending_messages($accessToken, $replyToken, $message_type, $return_message_text);


//メッセージの送信
function sending_messages($accessToken, $replyToken, $message_type, $return_message_text){
    //レスポンスフォーマット
    $response_format_text = [
        "type" => $message_type,
        "text" => $return_message_text
    ];
 
    //ポストデータ
    $post_data = [
        "replyToken" => $replyToken,
        "messages" => [$response_format_text]
    ];
 
    //curl実行
    $ch = curl_init("https://api.line.me/v2/bot/message/reply");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_PROXY,"http://fixie:lLQfKY2h6yaq478@velodrome.usefixie.com");
    curl_setopt($ch, CURLOPT_PROXYPORT,80);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charser=UTF-8',
        'Authorization: Bearer ' . $accessToken
    ));
    $result = curl_exec($ch);
    curl_close($ch);
}
?>