<?php
 
$accessToken = 'M12Yguz2fW3gq0AYBLk2m49F8VcL8HocX7Q+F5RM9zlHxfNns/mhFZvZKh77HAhvrT9RHuNORApTXUzr67gQhtq6FWl8GyD6oZFruqus8SM8xgumE1lvBHG5A2vEhItq5MYUX5//QEu4kXP3WVnKpQdB04t89/1O/w1cDnyilFU=';
 
//ユーザーからのメッセージ取得
$json_string = file_get_contents('php://input');
$json_object = json_decode($json_string);
 
//取得データ
$replyToken = $json_object->{"events"}[0]->{"replyToken"};        //返信用トークン
$message_type = $json_object->{"events"}[0]->{"message"}->{"type"};    //メッセージタイプ
$message_text = $json_object->{"events"}[0]->{"message"}->{"text"};    //メッセージ内容
 
//メッセージタイプが「text」以外のときは何も返さず終了
if($message_type != "text") exit;

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
		}else {
		$scoringPoints[$key] = "+".floor($basePoints[$key]);
		}
	}

	$totalPoints[$key] = intval($scoringPoints[$key])+$umaPoints[$i];
	if($totalPoints[$key]>0){
	$totalPoints[$key] = "+".$totalPoints[$key];
	}

	$return_message_text = $key . "さんは" . $scoringPoints[$key]."\t".$uma[$i]."\t".$totalPoints[$key]."\n".$return_message_text;
$i = $i-1;
}

$return_message_text = $return_message_text. "\nみなさん頑張ってくださいね～";

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