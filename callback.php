<?php
define('TOKEN', 'M12Yguz2fW3gq0AYBLk2m49F8VcL8HocX7Q+F5RM9zlHxfNns/mhFZvZKh77HAhvrT9RHuNORApTXUzr67gQhtq6FWl8GyD6oZFruqus8SM8xgumE1lvBHG5A2vEhItq5MYUX5//QEu4kXP3WVnKpQdB04t89/1O/w1cDnyilFU=');

//callbackŠm”F
$obj = json_decode(file_get_contents('php://input'));

//text‚ÆreplyTokenŽæ“¾
$event = $obj->{"events"}[0];
$text = $event->{"message"}->{"text"};
$replyToken = $event->{"replyToken"};

$post = [
    "replyToken" => $replyToken,
    "messages" => [
                    "type" => "text",
                    "text" => $text]
                  ];

$ch = curl_init("https://api.line.me/v2/bot/message/reply");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json; charser=UTF-8',
    'Authorization: Bearer ' . TOKEN;
    ));

?>