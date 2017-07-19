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
	//ウマの設定　ワンスリー
	//$uma = array("〇〇〇","〇　　","✕　　","✕✕✕");
	//$umaPoints = array(30,10,-10,-30);
	//ウマの設定　ゴットー
	$uma = array("〇〇","〇　","✕　","✕✕");
	$umaPoints = array(10,5,-5,-10);
  //場代調整 トップ-10
  $badai[] = {10,0,0,0};

	//トビ罰符の設定　10
	$tobiarr = array("ト"=>10,"ト2"=>20,"ト3"=>30,"ハ"=>-10);
	$gameResultTobi = array();
	//$qas ="ト";  //デバッグ用

	//DB接続用パラメーター
	$DB_SERVER = getenv('DB_HOST');
	$Port = "5432";
	$DB_NAME = getenv('DB_DATABASE');
	$DB_UID = getenv('DB_USERNAME');
	$DB_PASS = getenv('DB_PASSWORD');

	define("DB_CONECT","host=$DB_SERVER port=$Port dbname=$DB_NAME user=$DB_UID password=$DB_PASS");


	foreach($array as $value){
	    preg_match('/^([一-龥ぁ-ん]+)([-]*[0-9]+)(ト[1-3]?|ハ)?/', $value, $matches);
	    //$matches[1]; // 名前部分
	    //intval($matches[2]); // 得点部分
	    //$matches[3]; // 飛ばしハコ

			//得点が1/100で入力されることを補正 2017.07.12
			$gameResult = $gameResult + array($matches[1]=>intval($matches[2])*100);
			$gameResultTobi = $gameResultTobi+array($matches[1]=>$matches[3]);
			$qas = $matches[3];
			}

			//最後の行はコマンド　登録　修正　削除＋ゲーム番号
			$cmdstr = get_last_key($gameResult);
			$gameNm = get_last_value($gameResult)/100;
			if($cmdstr == '登録' || $cmdstr == '修正' || $cmdstr == '削除') {
					array_pop($gameResult); //コマンド行の内容を配列から削除
				}

	$sql_str = "INSERT INTO mjtable (date,time,handnumber,player,score,rank,scoringPoints,umaPoints,totalPoints,tobi) VALUES (";
	$sql = array();
	$sqlUpd = array();
	$sqlDel = array();

	$date_s=(string)date("Ymd");
	$endTime_s=date("H:i:s", strtotime('+9 hour'));
	$handnumber=$gameNm;
	$player_s="";
	$score_s=0;
	$rank_s=0;
	$scoringPoints_s=0;
	$umaPoints_s=0;
	$totalPoints_s=0;
	$totalCheck = 0;

	asort($gameResult);

	$i = 3;

	//飛ばし箱判定ルーチン
	foreach($gameResultTobi as $key => $value) {
		$gameResultTobi[$key] = $tobiarr[$gameResultTobi[$key]];
	}

	foreach($gameResult as $key => $value){

		$totalCheck += $gameResult[$key];

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

		$totalPoints[$key] = intval($scoringPoints[$key])+$umaPoints[$i]+$gameResultTobi[$key];
		if($totalPoints[$key]>0){
		$totalPoints[$key] = "+".$totalPoints[$key];
			} //if

		$player_s= $key;
		$score_s= intval($gameResult[$key]);
		$rank_s=$i+1;
		$scoringPoints_s=intval($scoringPoints[$key]);
		$umaPoints_s=intval($umaPoints[$i]);
		$totalPoints_s=intval($totalPoints[$key])-$badai[$i]; //場代調整修正2017.07.20
		$tobihakoPoints_s=intval($gameResultTobi[$key]);

		//insert登録の場合のSQL文
		$sql[$i]=$sql_str."'".$date_s."','".$endTime_s."',".$handnumber.",'".$player_s."',".$score_s.",".$rank_s.",".$scoringPoints_s.",".$umaPoints_s.",".$totalPoints_s.",".$tobihakoPoints_s.");";

		//update修正の場合のSQL文
		$sqlUpd[$i]="UPDATE mjtable SET date='".$date_s."',time='".$endTime_s."',handnumber=".$handnumber.",player='".$player_s."',score=".$score_s.",rank=".$rank_s.",scoringPoints=".$scoringPoints_s.",umaPoints=".$umaPoints_s.",totalPoints=".$totalPoints_s.", tobi=".$tobihakoPoints_s." WHERE player='".$player_s."' and date='".$date_s."' and handnumber=".$handnumber.";";

		//update削除の場合のSQL文
		$sqlDel[$i]="DELETE FROM mjtable WHERE player='".$player_s."' and date='".$date_s."' and handnumber=".$handnumber.";";

		//飛ばし箱の〇✕をウマの〇✕に反映
		$umatobiPt = $umaPoints[$i] + $gameResultTobi[$key];
		$umatobiPt = $umatobiPt/10;
		$umatobi = "　　　";

		if($umatobiPt>0) {
			$umatobi = str_repeat("〇",$umatobiPt);
		} elseif($umatobiPt<0) {
			$umatobiPt = $umatobiPt*-1;
			$umatobi = str_repeat("✕",$umatobiPt);
		}
		if(abs($umatobiPt)==1) { $umatobi = $umatobi."　　";}
		if(abs($umatobiPt)==2) { $umatobi = $umatobi."　";}

		$return_message_text = $key . "さん:" . $scoringPoints[$key]."\t".$umatobi."\t".$totalPoints[$key]."\n".$return_message_text;
		$i = $i-1;
		}

		$return_message_text =$return_message_text."持ち点合計".$totalCheck."\n";

		if($cmdstr=='登録') {
			$return_message_text = $return_message_text."\n登録モードです\n"."ゲーム番号".$gameNm;

			//DB接続
			// 各種パラメータを指定して接続
			$pg_conn = pg_connect(DB_CONECT);

			if( $pg_conn ) {
				$db_message = "接続に成功しました";

				//追加可能かのcheck
				$sqlCheck = "SELECT * FROM mjtable WHERE date='".$date_s."' and handnumber=".$handnumber.";";
				$resCheck = pg_query($pg_conn, $sqlCheck);
				if(pg_num_rows($resCheck)) {
					$db_message="既にゲームが登録されています";
				} else {
					// SQLクエリ実行
					$UpdRows=0;
					for($n=0;$n<4;$n++){
					$res = pg_query( $pg_conn, $sql[$n]);
					$UpdRows += pg_affected_rows($res);
					}

					$db_message = $UpdRows."件データ登録しました";
				}
			} else {
				$db_message = "接続できませんでした";
			}

			// データベースとの接続を切断
			pg_close($pg_conn);


			$return_message_text = $return_message_text."\n".$db_message."\n".pg_num_rows($resCheck);

		} elseif($cmdstr=='修正') {
			$return_message_text =  $return_message_text."\n修正モードです\n"."ゲーム番号".$gameNm;

		//DB接続
		// 各種パラメータを指定して接続
			$pg_conn = pg_connect(DB_CONECT);

			if( $pg_conn ) {
				$db_message = "接続に成功しました";

				// SQLクエリ実行
				$UpdRows=0;
				for($n=0;$n<4;$n++){
				$res = pg_query( $pg_conn, $sqlUpd[$n]);
				$UpdRows += pg_affected_rows($res);
				}

				if($res) {
				$db_message = $UpdRows."件データ更新しました";
				} else {
				$db_message = "データ更新できませんでした";
				}

			} else {
				$db_message = "接続できませんでした";
			}

			// データベースとの接続を切断
			pg_close($pg_conn);

			$return_message_text =$return_message_text."\n".$db_message."\n";

		} elseif($cmdstr=='削除') {
			$return_message_text =  $return_message_text."\n削除モードです\n"."ゲーム番号".$gameNm."\n";

		//DB接続
		// 各種パラメータを指定して接続
			$pg_conn = pg_connect(DB_CONECT);

			if( $pg_conn ) {
				$db_message = "接続に成功しました";

				$UpdRows=0;
				for($n=0;$n<4;$n++){
				$res = pg_query( $pg_conn, $sqlDel[$n]);
				$UpdRows += pg_affected_rows($res);
				}

				$db_message = $UpdRows."件データ削除しました";

			} else {
				$db_message = "接続できませんでした";
			}

			// データベースとの接続を切断
			pg_close($pg_conn);

			$return_message_text =  $return_message_text.$db_message;

		} else {	//表示モード

			$return_message_text = $return_message_text. "\n確認の上、登録ください";
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

?>
