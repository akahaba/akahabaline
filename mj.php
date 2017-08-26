<?php
  /* ### "pg_fetch_all()"によるデータ取得 ### */

//DB接続用パラメーター
$DB_SERVER = getenv('DB_HOST');
$Port = "5432";
$DB_NAME = getenv('DB_DATABASE');
$DB_UID = getenv('DB_USERNAME');
$DB_PASS = getenv('DB_PASSWORD');

date_s="20170825"; //デバッグ用

define("DB_CONECT","host=$DB_SERVER port=$Port dbname=$DB_NAME user=$DB_UID password=$DB_PASS");

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

echo $val;

}
  $db_message = "クエリ実行できました";
} else {
  $db_message = "クエリ実行できまませんでした";
}

//データベースとの接続を切断
pg_close($pg_conn);

echo $db_message;
?>