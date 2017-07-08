<?php

function return_score($message_text) 
{

	//JSON用変数宣言
	$arrGame = array("date"=>date("Ymd"),"endTime"=>date("H:i:s", strtotime('+9 hour')));

	$array = explode("\n", $message_text); // とりあえず行に分割
	$array = array_map('trim', $array); // 各行にtrim()をかける
	$array = array_filter($array, 'strlen'); // 文字数が0の行を取り除く
	$array = array_values($array); // これはキーを連番に振りなおしてるだけ

	$points = array();
	$basePoints = array();
	$scoringPoints = array();
	$totalPoints = array();
	$return_message_textscore = array();
	$gameResult = array();
	$oka = 0;
	$i = 0;
	$uma = array("〇〇〇","〇　　","✕　　","✕✕✕");
	$umaPoints = array(30,10,-10,-30);

	foreach($array as $value){
	    preg_match('/^([一-龥ぁ-ん]+)([-]*[0-9]+)/', $value, $matches);
	 
	    //$matches[1]; // 名前部分
	    //intval($matches[2]); // 得点部分

		$gameResult = $gameResult + array($matches[1]=>intval($matches[2]));
		}

	//最後の行はコマンド　登録　修正　削除＋ゲーム番号
			$cmdstr = get_last_key($gameResult);
			$gameNm = get_last_value($gameResult);
	if($cmdstr == '登録' || $cmdstr == '修正' || $cmdstr == '削除') {
			array_pop($gameResult);
		}
	
	//$sql = "INSERT INTO mjtable (date,time,player,score,rank,scoringPoints,umaPoints,totalPoints
	//) VALUES ($player,$score,$rank,$scoringPoints,$umaPoints,$totalPoints)";
	$sql_str = "INSERT INTO mjtable (date,time,handnumber,player,score,rank,scoringPoints,umaPoints,totalPoints) VALUES (";
	$sql = array();
	
	$date_s=(string)date("Ymd");
	$endTime_s=date("H:i:s", strtotime('+9 hour'));
	$handnumber=$gameNm;
	$player_s="";
	$score_s=0;
	$rank_s=0;
	$scoringPoints_s=0;
	$umaPoints_s=0;
	$totalPoints_s=0;
	
	asort($gameResult);
    
	$i = 3;
	foreach($gameResult as $key => $value){

		$basePoints[$key] = ($gameResult[$key] - 30000)/1000;
		if($basePoints[$key]<0){
			$oka = $oka + ceil($basePoints[$key]);
			$scoringPoints[$key] = ceil($basePoints[$key]);
		} else {
			$oka = $oka + floor($basePoints[$key]);
			if($i==0){
			$scoringPoints[$key] = "+".(floor($basePoints[$key])-$oka);
			} else {
			$scoringPoints[$key] = "+".floor($basePoints[$key]);
				}
		}

		$totalPoints[$key] = intval($scoringPoints[$key])+$umaPoints[$i];
		if($totalPoints[$key]>0){
		$totalPoints[$key] = "+".$totalPoints[$key];
			} //if

		$player_s= $key;
		$score_s= intval($gameResult[$key]);
		$rank_s=$i+1;
		$scoringPoints_s=intval($scoringPoints[$key]);
		$umaPoints_s=intval($umaPoints[$i]);
		$totalPoints_s=intval($totalPoints[$key]);

		//JSON用arrayへの代入
		//$arrPlayerResult = array("rank"=>($i+1),"score"=>$gameResult[$key],"scoringPoints"=>$scoringPoints[$key],"umaPoints"=>$umaPoints[$i],"totalPoints"=>$totalPoints[$key]);
		//$result = array_merge($arrGame,"name"=>$key);
		//$arrGame += array($key=>$arrPlayerResult);

		//.$date_s."','".$endTime_s."','"
		//$sql[$i]= $sql_str. $key .",". $gameResult[$key].",".$scoringPoints[$key]).",". $umaPoints[$i].",".$totalPoints[$key].");";
		$sql[$i]=$sql_str."'".$date_s."','".$endTime_s."',".$handnumber.",'".$player_s."',".$score_s.",".$rank_s.",".$scoringPoints_s.",".$umaPoints_s.",".$totalPoints_s.");";
		$return_message_text = $key . "さんは" . $scoringPoints[$key]."\t".$uma[$i]."\t".$totalPoints[$key]."\n".$return_message_text;
		$i = $i-1;
		}

		//$arrGame = json_encode($arrGame);
		//$arrPlayerResult = json_encode($arrPlayerResult);
		//file_put_contents("/tmp/test.json" , $arrGame);


		if($cmdstr=='登録') {
			$return_message_text = $return_message_text."\n登録モードです\n"."ゲーム番号".$gameNm;

			$sqlreturn_message_text = record_score($sql);

			$return_message_text = $return_message_text.$sqlreturn_message_text;
		
		} elseif($cmdstr=='修正') {
			$return_message_text =  $return_message_text."\n修正モードです\n"."ゲーム番号".$gameNm;
		} elseif($cmdstr=='削除') {
			$return_message_text =  $return_message_text."\n削除モードです\n"."ゲーム番号".$gameNm;
		} else {	//表示モード
		
			$return_message_text = $return_message_text. "\nみなさん頑張ってくださいね～";
		}


	return $return_message_text;

}


//第一引数・・・最後のキーを取得したい配列
//返り値・・・最後のキー
function get_last_key($array)
{
    end($array);
    return key($array);
}

//第一引数・・・最後の値を取得したい配列
//返り値・・・最後の値
function get_last_value($array)
{
    return end($array);
}

//record
function record_score($arr) {

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

	foreach($arr as $value) {
	// SQLクエリ実行
	$res = pg_query( $pg_conn, $value);
	var_dump($res);
	}

	}
	$return_text = "データ登録しました";
	
} else {
	$return_text = "接続できませんでした";
}

// データベースとの接続を切断
pg_close($pg_conn);
return $return_text;
}


?>