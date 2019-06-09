<?php

function bot($event) {
	//reply($event, 'こんにちは！');
	$replyToken = $event->{"replyToken"};
	$messageId = $event->{"message"}->{"id"};


	//画像ファイルのバイナリ取得
	$ch = curl_init("https://api.line.me/v2/bot/message/".$messageId."/content");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . TOKEN));
	curl_setopt($ch, CURLOPT_ENCODING, null);
	$result = curl_exec($ch);
	curl_close($ch);
	// 画像認識
	$recognizedText = imageRecognize($result);
	$translatedText = translate($recognizedText);
	reply($event, $translatedText);

}
