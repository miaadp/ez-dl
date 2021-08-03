<?php
$host = 'localhost';
$user = 'down';
$pass = 'KM&{z?N-b*mT';
$database = 'down';
$db = new mysqli($host,$user,$pass,$database);
if ($db->connect_errno)die('db_error');
if(isset($_GET[array_keys($_GET)[0]]) && strlen(array_keys($_GET)[0]) == 32){
    $link = array_keys($_GET)[0];
}
$db->close();
exit();
use danog\MadelineProto\API;
if (!file_exists('madeline.php')) copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');include 'madeline.php';
$MadelineProto = new API('bot.madeline');$MadelineProto->start();
$info = $MadelineProto->messages->getMessages(['id' => [$_GET['id']]])['messages'][0];
file_put_contents('update.json',json_encode($info,128|256));
$from_id = $info['peer_id']['user_id'];
$media = $info['media'];
if (isset($info['media']['document']['attributes'][0]['file_name'])){
    $filename = $info['media']['document']['attributes'][0]['file_name'];
}
elseif (isset($info['media']['document']['attributes'][1]['file_name'])){
    $filename = $info['media']['document']['attributes'][1]['file_name'];
}
else{
    $filename = 'unknown';
}
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$old_ie = (bool)preg_match('#MSIE [3-8]\.#', $ua);
if (preg_match('/^[a-zA-Z0-9_.-]+$/', $filename)) {
    $header = 'filename="' . $filename . '"';
}
elseif ($old_ie || preg_match('#Firefox/(\d+)\.#', $ua, $matches) && $matches[1] < 5) {
    $header = 'filename="' . rawurlencode($filename) . '"';
}
elseif (preg_match('#Chrome/(\d+)\.#', $ua, $matches) && $matches[1] < 11) {
    $header = 'filename=' . $filename;
}
elseif (preg_match('#Safari/(\d+)\.#', $ua, $matches) && $matches[1] < 6) {
    $header = 'filename=' . $filename;
}
elseif (preg_match('#Android #', $ua, $matches)) {
    $header = 'filename="' . $filename . '"';
}
else {
    $header = "filename*=UTF-8''" . rawurlencode($filename) . '; filename="' . rawurlencode($filename) . '"';
}
header('Content-Disposition: attachment; ' . $header);
$MadelineProto->downloadToBrowser($media);
