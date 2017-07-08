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
 
//メッセージタイプが「text」以外のときは何も返さず終了
if($message_type != "text") exit;

if(strpos($message_text,'確認') !== false){
//  if(preg_match('/^([確認]+)/',$message_text)) {
  //messageの先頭に'確認'が含まれている場合

	$return_message_text = "現在の結果だよ！";
//	$return_message_text = record_score();

	$sqlcmd="SELECT player, Sum(totalpoints) FROM mjtable GROUP BY player;";

		//DB接続
		// 各種パラメータを指定して接続
			$pg_conn = pg_connect(DB_CONECT);

			if( $pg_conn ) {
				$db_message = "接続に成功しました";

				// SQLクエリ実行
				$res = pg_query( $pg_conn, $sqlcmd);
				//var_dump($res);

			$resultScore ="";
			for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
			    $rows = pg_fetch_array($res, NULL, PGSQL_ASSOC);
			    $resultScore=$resultScore.$rows['player']."\t".$rows['Sum(totalpoints)']."\n";
			}



				$db_message = "クエリ実行できました";
				
			} else {
				$db_message = "クエリ実行できまませんでした";
			}

			// データベースとの接続を切断
			pg_close($pg_conn);

	$return_message_text=$return_message_text."\n\n".$resultScore;

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