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
 
//返信メッセージ
//$return_message_text = "「" . $message_text . "」じゃねーよｗｗｗ";
//$return_message_text = "2017/7/3 10回戦(23:45終了)\n
//朝倉　-50\t-20\t✕✕✕\n甘蔗　+10\t±0\t〇\n嵯峨　-21\t-11\t✕\n寳閣　+61\t+31\t〇〇〇\nトータル\n嵯峨　+66\n甘蔗　+37\n朝倉　+31\n寳閣　-134\n\nみんな頑張って！";

$array = explode("\n", $message_text); // とりあえず行に分割
$array = array_map('trim', $array); // 各行にtrim()をかける
$array = array_filter($array, 'strlen'); // 文字数が0の行を取り除く
$array = array_values($array); // これはキーを連番に振りなおしてるだけ


$str = $array(0);
    preg_match('/^([一-龥ぁ-ん]+)([-]*[0-9]+)/', $str, $matches);
 
    $matches[1]; // 名前部分
    $matches[2]; // 得点部分

$return_message_text = $matches[1] . "さんは" . $matches[2] . "点でした。";
//$return_message_text = $message_text;

//返信実行
sending_messages($accessToken, $replyToken, $message_type, $return_message_text);
?>
<?php
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