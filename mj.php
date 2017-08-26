$con    = pg_connect("dbname=TEST");  // データベースに接続
  $result = pg_exec($con, "SELECT * FROM zaiko ;"); // クエリを発行
  $rows   = pg_numrows($result);  // レコードの総数を取得
  $row    = 0;  // 行カウンタを初期化

  echo "<TABLE border=1><TR><TH>hinmei</TH><TH>zaiko</TH></TR>\n";

  while( $row < $rows ){
    $DATA = pg_fetch_object( $result, $row );  // 結果セットからレコードを1行取得する
    echo "<TR><TD>{$DATA->hinmei}</TD><TD>{$DATA->zaiko}</TD></TR>\n";
    // オブジェクトの場合は、変数名->カラム名 でそのカラムのデータを参照できます

    $row ++;  // 行カウンタを進める
  }
  echo "</TABLE>\n";