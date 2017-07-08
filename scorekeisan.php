<?php

function return_score($message_text) {

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
			}

		//JSON用arrayへの代入
		//$arrPlayerResult = array("rank"=>($i+1),"score"=>$gameResult[$key],"scoringPoints"=>$scoringPoints[$key],"umaPoints"=>$umaPoints[$i],"totalPoints"=>$totalPoints[$key]);
		//$result = array_merge($arrGame,"name"=>$key);
		//$arrGame += array($key=>$arrPlayerResult);

		$return_message_text = $key . "さんは" . $scoringPoints[$key]."\t".$uma[$i]."\t".$totalPoints[$key]."\n".$return_message_text;
		$i = $i-1;
		}

		//$arrGame = json_encode($arrGame);
		//$arrPlayerResult = json_encode($arrPlayerResult);
		//file_put_contents("/tmp/test.json" , $arrGame);

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