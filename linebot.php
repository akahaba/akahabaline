<?php
require "scorekeisan.php";

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
//$date_s="20171016"; //デバッグ用
//精算レート 点５->50 点ピン->100
$ratevalue=50;

//メッセージタイプが「text」以外のときは何も返さず終了
if($message_type != "text") exit;

//messageの先頭に'確認'が含まれている場合
if(strpos($message_text,'確認') !== false){

	$return_message_text = "現在の結果だよ!";

		//DB接続
		// 各種パラメータを指定して接続
			$pg_conn = pg_connect(DB_CONECT);

			if( $pg_conn ) {
				$db_message = "接続に成功しました";

				// SQLクエリ実行 終了ゲーム数
				$sqlhndno ="SELECT MAX(handnumber) FROM mjtable WHERE date='".$date_s."';";
				$resHandnumber = pg_query( $pg_conn, $sqlhndno);
				//終了ゲーム数の取得
				$val = pg_fetch_result($resHandnumber, 0, 0);

				// SQLクエリ実行　現在のスコア状況
        $sqlcmd="SELECT player, Sum(scoringpoints) As pt,Sum(umapoints) As uma,Sum(tobi) As tobi,Sum(totalpoints) As total FROM mjtable WHERE date='".$date_s."' GROUP BY player order by total desc;";
  			$res = pg_query( $pg_conn, $sqlcmd);

  			//現時点でのスコア状況の取得
  			$resultScore ="";
  			for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
  			    $rows = pg_fetch_array($res, NULL, PGSQL_ASSOC);
  			    $resultScore=$resultScore.$rows['player'].str_pad($rows['pt'], 5, " ", STR_PAD_LEFT).str_pad($rows['uma'], 5, " ", STR_PAD_LEFT).str_pad($rows['tobi'], 5, " ", STR_PAD_LEFT).str_pad($rows['total'], 5, " ", STR_PAD_LEFT)."\n";
  			}
				$db_message = "クエリ実行できました";
			} else {
				$db_message = "クエリ実行できまませんでした";
			}

			//データベースとの接続を切断
			pg_close($pg_conn);

			//ゲーム数０の場合の切り分け $valは終了ゲーム数
			if($val>0) { //ゲーム数０より大きい場合
				$return_message_text=$return_message_text."\n\n".$val."回戦終了時点トータル\n".$resultScore;
			} else {     //ゲーム数０の場合（偽）
				$return_message_text=$return_message_text."\n\n本日、記録されているゲーム結果はありません";
			}
//messageの先頭に'履歴'が含まれている場合
} elseif(strpos($message_text,'履歴') !== false) {

  		//DB接続
  		// 各種パラメータを指定して接続
			$pg_conn = pg_connect(DB_CONECT);

			if( $pg_conn ) {
				$db_message = "接続に成功しました";

				//参加者名の取得
				$playerToday = array();
        $sqlPlayer="select player from mjtable where date='".$date_s."' group by player order by player desc;";
				$resPlayer = pg_query( $pg_conn, $sqlPlayer);
				for ($i = 0 ; $i < pg_num_rows($resPlayer) ; $i++){
				    $rows = pg_fetch_array($resPlayer, NULL,PGSQL_NUM );
				$playerToday[$i]=$rows[0];
				}

				//$sqlrollupRank = "select handnumber,sum(case player when '".$playerToday[0]."' then rank else 0 end) ,sum(case player when '".$playerToday[1]."' then rank else 0 end) ,sum(case player when '".$playerToday[2]."' then rank else 0 end) ,sum(case player when '".$playerToday[3]."' then rank else 0 end) from mjtable where date='".$date_s."' group by rollup(handnumber) order by handnumber asc;";

				// SQLクエリ実行 得点履歴の取得
        $sqlrollup = "select handnumber,sum(case player when '".$playerToday[0]."' then totalpoints else 0 end) ,sum(case player when '".$playerToday[1]."' then totalpoints else 0 end) ,sum(case player when '".$playerToday[2]."' then totalpoints else 0 end) ,sum(case player when '".$playerToday[3]."' then totalpoints else 0 end) from mjtable where date='".$date_s."' group by rollup(handnumber) order by handnumber asc;";
				$res = pg_query( $pg_conn, $sqlrollup);

				// SQLクエリ実行 終了ゲーム数
				$sqlhndno ="SELECT MAX(handnumber) FROM mjtable WHERE date='".$date_s."';";
				$resHandnumber = pg_query( $pg_conn, $sqlhndno);
				//終了ゲーム数
				$val = pg_fetch_result($resHandnumber, 0, 0);
  			//ゲーム履歴の取得
  			$resultScore ="";
  			for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
  			    $rows = pg_fetch_array($res, NULL,PGSQL_NUM );
  			    $resultScore=$resultScore.str_pad($rows[0], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[1], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[2], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[3], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[4], 5, " ", STR_PAD_LEFT)."|\n";
  			}

				$db_message = "クエリ実行できました";

			} else {
				$db_message = "クエリ実行できまませんでした";
			}

			// データベースとの接続を切断
			pg_close($pg_conn);

    	//ゲーム数０の切り分け
    	if($val>0) {
    	$headertitle=str_pad("回戦", 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[0], 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[1], 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[2], 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[3], 6, " ", STR_PAD_LEFT)."|"."\n";
    	$devidechr="----+----+----+----+----+\n";
    	$return_message_text=$return_message_text."本日のゲームの履歴です"."\n".$headertitle.$devidechr.$resultScore;
    	} else {
    	$return_message_text=$return_message_text."本日、記録されているゲーム結果はありません";
    	}

//順位履歴の表示
} elseif(strpos($message_text,'順位') !== false) {
	//messageに順位が含まれている場合

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
				}
				//順位履歴を取得するSQL文
				$sqlrollupRank = "select handnumber,sum(case player when '".$playerToday[0]."' then rank else 0 end) ,sum(case player when '".$playerToday[1]."' then rank else 0 end) ,sum(case player when '".$playerToday[2]."' then rank else 0 end) ,sum(case player when '".$playerToday[3]."' then rank else 0 end) from mjtable where date='".$date_s."' group by handnumber order by handnumber asc;";
				//平均順位を計算するための順位合計値の取得SQL文
				$sqlranktotal_0 = "select sum(rank) from mjtable where date='".$date_s."' and player='".$playerToday[0]."';";
				$sqlranktotal_1 = "select sum(rank) from mjtable where date='".$date_s."' and player='".$playerToday[1]."';";
				$sqlranktotal_2 = "select sum(rank) from mjtable where date='".$date_s."' and player='".$playerToday[2]."';";
				$sqlranktotal_3 = "select sum(rank) from mjtable where date='".$date_s."' and player='".$playerToday[3]."';";

				// SQLクエリ実行
				$res = pg_query( $pg_conn, $sqlrollupRank);

				$resRank0 = pg_query( $pg_conn, $sqlranktotal_0);
				$resRank1 = pg_query( $pg_conn, $sqlranktotal_1);
				$resRank2 = pg_query( $pg_conn, $sqlranktotal_2);
				$resRank3 = pg_query( $pg_conn, $sqlranktotal_3);

				$valRank0 = pg_fetch_result($resRank0, 0, 0);
				$valRank1 = pg_fetch_result($resRank1, 0, 0);
				$valRank2 = pg_fetch_result($resRank2, 0, 0);
				$valRank3 = pg_fetch_result($resRank3, 0, 0);

				// SQLクエリ実行 終了ゲーム数
				$sqlhndno ="SELECT MAX(handnumber) FROM mjtable WHERE date='".$date_s."';";
				$resHandnumber = pg_query( $pg_conn, $sqlhndno);
				//終了ゲーム数
				$val = pg_fetch_result($resHandnumber, 0, 0);

				//平均順位の計算
				$valRank0avg= number_format(round($valRank0/$val,2), 2);
				$valRank1avg= number_format(round($valRank1/$val,2), 2);
				$valRank2avg= number_format(round($valRank2/$val,2), 2);
				$valRank3avg= number_format(round($valRank3/$val,2), 2);

  			//ゲーム履歴の取得
  			$resultScore ="";
  			for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
  			    $rows = pg_fetch_array($res, NULL,PGSQL_NUM );
  			    $resultScore=$resultScore.str_pad($rows[0], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[1], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[2], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[3], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[4], 5, " ", STR_PAD_LEFT)."|\n";
  			}

  				$db_message = "クエリ実行できました";

  			} else {
  				$db_message = "クエリ実行できまませんでした";
  			}

  			// データベースとの接続を切断
			pg_close($pg_conn);

      	//ゲーム数０の切り分け
      	if($val>0) {
      	$headertitle=str_pad("回戦", 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[0], 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[1], 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[2], 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[3], 6, " ", STR_PAD_LEFT)."|"."\n";
      	$devidechr="----+----+----+----+----+\n";
      	$footertotalavg=str_pad(" ", 4, " ", STR_PAD_LEFT)."|".str_pad($valRank0avg, 4, " ", STR_PAD_LEFT)."|".str_pad($valRank1avg, 4, " ", STR_PAD_LEFT)."|".str_pad($valRank2avg, 4, " ", STR_PAD_LEFT)."|".str_pad($valRank3avg, 4, " ", STR_PAD_LEFT)."|"."\n";
      	$return_message_text=$return_message_text."本日のゲームの順位履歴です"."\n".$headertitle.$devidechr.$resultScore.$footertotalavg;
      	//$return_message_text=$return_message_text.$valRank0."\n".$val."\n".$sqlranktotal_0;
      	} else {
      	$return_message_text=$return_message_text."本日、記録されているゲーム結果はありません";
      	}

//順位分布の表示
} elseif(strpos($message_text,'分布') !== false) {
	//messageに分布が含まれている場合

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
				}
				//順位分布を取得するSQL文
				$sqlRankdistribution = "select rank,sum(case player when '".$playerToday[0]."' then 1 else 0 end),sum(case player when '".$playerToday[1]."' then 1 else 0 end),sum(case player when '".$playerToday[2]."' then 1 else 0 end),sum(case player when '".$playerToday[3]."' then 1 else 0 end) from mjtable where date='".$date_s."' group by rank;";

				//$sqlRankdistribution = "select rank as "順位",sum(case player when '".$playerToday[0]."' then 1 else 0 end),sum(case player when '".$playerToday[1]."' then 1 else 0 end),sum(case player when '".$playerToday[2]."' then 1 else 0 end),sum(case player when '".$playerToday[3]."' then 1 else 0 end) from mjtable group by rank;";
				// SQLクエリ実行
				$res = pg_query( $pg_conn, $sqlRankdistribution);

				// SQLクエリ実行 終了ゲーム数
				$sqlhndno ="SELECT MAX(handnumber) FROM mjtable WHERE date='".$date_s."';";
				$resHandnumber = pg_query( $pg_conn, $sqlhndno);
				//終了ゲーム数
				$val = pg_fetch_result($resHandnumber, 0, 0);


  			//ゲーム履歴の取得
  			$resultScore ="";
  			for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
  			    $rows = pg_fetch_array($res, NULL,PGSQL_NUM );
  			    $resultScore=$resultScore.str_pad($rows[0], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[1], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[2], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[3], 5, " ", STR_PAD_LEFT)."|".str_pad($rows[4], 5, " ", STR_PAD_LEFT)."|\n";
  			}

  				$db_message = "クエリ実行できました";

  			} else {
  				$db_message = "クエリ実行できまませんでした";
  			}

  			// データベースとの接続を切断
			pg_close($pg_conn);

      	//ゲーム数０の切り分け
      	if($val>0) {
      	$headertitle=str_pad("順位", 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[0], 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[1], 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[2], 6, " ", STR_PAD_LEFT)."|".str_pad($playerToday[3], 6, " ", STR_PAD_LEFT)."|"."\n";
      	$devidechr="----+----+----+----+----+\n";
      	//$footertotalavg=str_pad(" ", 4, " ", STR_PAD_LEFT)."|".str_pad($valRank0avg, 4, " ", STR_PAD_LEFT)."|".str_pad($valRank1avg, 4, " ", STR_PAD_LEFT)."|".str_pad($valRank2avg, 4, " ", STR_PAD_LEFT)."|".str_pad($valRank3avg, 4, " ", STR_PAD_LEFT)."|"."\n";
      	$footertotalavg="";
      	$return_message_text=$return_message_text."本日のゲームの順位分布です"."\n".$headertitle.$devidechr.$resultScore.$footertotalavg;
      	//$return_message_text=$return_message_text.$valRank0."\n".$val."\n".$sqlranktotal_0;
      	} else {
      	$return_message_text=$return_message_text."本日、記録されているゲーム結果はありません";
      	}
//ここまで


//messageに'精算'が含まれている場合 精算内容の表示
} elseif(strpos($message_text,'精算') !== false){
	$return_message_text = "本日の精算額はこちら！";

	$sqlcmd="SELECT player,Sum(totalpoints)*$ratevalue As total FROM mjtable WHERE date='".$date_s."' GROUP BY player order by total desc;";

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
			    $resultScore=$resultScore.$rows['player'].str_pad($rows['total'],10, " ", STR_PAD_LEFT)."円\n";
			}

				$db_message = "クエリ実行できました";

			} else {
				$db_message = "クエリ実行できまませんでした";
			}

			// データベースとの接続を切断
			pg_close($pg_conn);

			//ゲーム数０の切り分け
			if($val>0) {
			$return_message_text=$return_message_text."\n\n".$val."回戦終了時点精算額\n".$resultScore;
			} else {
			$return_message_text=$return_message_text."\n本日、記録されているゲーム結果はありません";
			}

//messageに'確認''履歴''精算'が含まれていない場合
} else {
  //messageに数字（得点）が含まれている場合、スコア計算関数の起動
	if(preg_match('/^[^0-9]+[0-9]+/',$message_text)) {
		$return_message_text = return_score($message_text);
	} else {
		$return_message_text = "麻雀したいなぁ～～～\n麻雀できる日あったら教えてね～～";
	}
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
