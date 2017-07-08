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
	var_dump("接続に成功しました");
} else {
	var_dump("接続できませんでした");
}

// データベースとの接続を切断
pg_close($pg_conn);

}
?>