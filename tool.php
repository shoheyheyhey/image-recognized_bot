<?php

define('TOKEN', '取得したトークン');
define('API_KEY','取得したAPIキー');

if (file_exists(DEBUG)) unlink(DEBUG);

function debug($title, $text) {
	file_put_contents(DEBUG, '['.$title.']'."\n".$text."\n\n", FILE_APPEND);
}

function heroku_debug($text) {
	$stdout= fopen( 'php://stdout', 'w' );
	fwrite( $stdout, "[DEBUG]={$text}\n" );
}

function post($url, $object) {
	$json=json_encode($object);
	debug('output', $json);

	$curl=curl_init('https://api.line.me/v2/bot/message/'.$url);
	curl_setopt($curl, CURLOPT_POST, TRUE);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
	curl_setopt($curl, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Authorization: Bearer '.TOKEN
	]);

	$result=curl_exec($curl);
	debug('result', $result);

	curl_close($curl);
}

function reply($event, $text) {
	$object=[
		'replyToken'=>$event->replyToken,
		'messages'=>[['type'=>'text', 'text'=>$text]]
	];
	post('reply', $object);
}

function reply_image($event, $original, $preview) {
	$object=[
		'replyToken'=>$event->replyToken,
		'messages'=>[[
			'type'=>'image',
			'originalContentUrl'=>$original,
			'previewImageUrl'=>$preview
		]]
	];
	post('reply', $object);
}

function push($to, $text) {
	$object=[
		'to'=>$to,
		'messages'=>[['type'=>'text', 'text'=>$text]]
	];
	post('push', $object);
}

function load($file) {
	$json=file_get_contents($file);
	return json_decode($json);
}

function save($file, $object) {
	$json=json_encode($object);
	file_put_contents($file, $json);
}

function lock($file) {
	$fp=fopen($file, 'c');
	flock($fp, LOCK_EX);
	return $fp;
}

function unlock($fp) {
	flock($fp, LOCK_UN);
	fclose($fp);
}

// 翻訳(英語→日本語)処理
function translate($text) {
	$handle = curl_init();

	if (FALSE === $handle)
	   throw new Exception('failed to initialize');

	curl_setopt($handle, CURLOPT_URL,'https://www.googleapis.com/language/translate/v2');
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($handle, CURLOPT_POSTFIELDS, array('key'=> API_KEY, 'q' => $text, 'source' => 'en', 'target' => 'ja'));
	curl_setopt($handle,CURLOPT_HTTPHEADER,array('X-HTTP-Method-Override: GET'));
	$json = curl_exec($handle);
	$array_json = json_decode($json, true);
	$translated = $array_json["data"]["translations"]["0"]["translatedText"];
	print($translated);
	return $translated;
}

// 画像認識処理
function imageRecognize($image) {
	// リクエスト用のJSONを作成
	$json = json_encode( array(
		"requests" => array(
			array(
				"image" => array(
					"content" => base64_encode($image) ,
				) ,
				"features" => array(
					// array(
					// 	"type" => "FACE_DETECTION" ,
					// 	"maxResults" => 3 ,
					// ) ,
					// array(
					// 	"type" => "LANDMARK_DETECTION" ,
					// 	"maxResults" => 3 ,
					// ) ,
					// array(
					// 	"type" => "LOGO_DETECTION" ,
					// 	"maxResults" => 3 ,
					// ) ,
					// array(
					// 	"type" => "LABEL_DETECTION" ,
					// 	"maxResults" => 3 ,
					// ) ,
					// array(
					// 	"type" => "TEXT_DETECTION" ,
					// 	"maxResults" => 3 ,
					// ) ,
					// array(
					// 	"type" => "SAFE_SEARCH_DETECTION" ,
					// 	"maxResults" => 3 ,
					// ) ,
					// array(
					// 	"type" => "IMAGE_PROPERTIES" ,
					// 	"maxResults" => 3 ,
					// ) ,
	        array(
	          "type" => "WEB_DETECTION" ,
	          "maxResults" => 1 ,
	        ) ,
				) ,
			) ,
		) ,
	) ) ;

	$curl = curl_init() ;
	curl_setopt( $curl, CURLOPT_URL, "https://vision.googleapis.com/v1/images:annotate?key=" . API_KEY ) ;
	curl_setopt( $curl, CURLOPT_HEADER, true ) ;
	curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "POST" ) ;
	curl_setopt( $curl, CURLOPT_HTTPHEADER, array( "Content-Type: application/json" ) ) ;
	curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false ) ;
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ) ;
	if( isset($referer) && !empty($referer) ) curl_setopt( $curl, CURLOPT_REFERER, $referer ) ;
	curl_setopt( $curl, CURLOPT_TIMEOUT, 15 ) ;
	curl_setopt( $curl, CURLOPT_POSTFIELDS, $json ) ;
	$res1 = curl_exec( $curl ) ;
	$res2 = curl_getinfo( $curl ) ;
	curl_close( $curl ) ;
	// 取得したデータ
	$json = substr( $res1, $res2["header_size"] ) ;
	heroku_debug($json);
	$array_json=json_decode($json, true);
	$text=$array_json["responses"]["0"]["webDetection"]["webEntities"]["0"]["description"];
	print($text);
	return $text;
}
