<?php
function record_score() {

$DB_SERVER = getenv('DB_HOST');
$Port = "5432";
$DB_NAME = getenv('DB_DATABASE');
$DB_UID = getenv('DB_USERNAME');
$DB_PASS = getenv('DB_PASSWORD');

define("DB_CONECT","host=$DB_SERVER port=$Port dbname=$DB_NAME user=$DB_UID password=$DB_PASS");

// 各種パラメータを指定して接続
$pg_conn = pg_connect(DB_CONECT);
//$pg_conn = pg_connect("host=localhost port=5432 dbname=test user=testuser password=testtest");

if( $pg_conn ) {
	$return_text = "接続に成功しました";

	//$datestr = '20170708';
	//$datestr = $obj->{'date'};
	//$timestr = strval($obj['endTime']);

	//$player = $obj[$i];
	//$score = $obj[$i]["score"];
	//$rank = $obj[$i]["rank"];
	//$scoringPoints = $obj[$i]["scoringPoints"];
	//$umaPoints = $obj[$i]["umaPoints"];
	//$totalPoints = $obj[$i]["totalPoints"];
	
	//$player = '甘蔗';
	//$score = 32600;
	//$rank = 1;
	//$scoringPoints = 21;
	//$umaPoints = 30;
	//$totalPoints = 51;
	
	//$sql = "INSERT INTO mjtable (
	date,time,player,score,rank,scoringPoints,umaPoints,totalPoints
) VALUES (
	$player,$score,$rank,$scoringPoints,$umaPoints,$totalPoints
	)";
	
	// SQLクエリ実行
	//$res = pg_query( $pg_conn, $sql);
	//var_dump($res);
	//}
	//$return_text = "データ登録しました";
	
} else {
	$return_text = "接続できませんでした";
}

// データベースとの接続を切断
pg_close($pg_conn);
return $return_text;
}
?>