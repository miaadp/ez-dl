<?php

use danog\MadelineProto\API;
if (!file_exists('madeline.php'))copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
if ($_SERVER['REQUEST_URI'] !== '/') {
    $org_request = explode('/', str_replace(basename(__FILE__), '', $_SERVER['REQUEST_URI']));
    $type = strtolower(explode('?', $org_request[1])[0]);
    if ($type === 'player') {
        if (isset($org_request[2])) {
            $id_hex = explode('?', $org_request[2])[0];
            if (ctype_xdigit($id_hex)){
                $link = getenv('domain').'/'.$id_hex;
                $name = getenv('name');
                echo '    <!DOCTYPE html><html lang=fa dir=rtl><head><meta charset=UTF-8><meta http-equiv=X-UA-Compatible content="IE=edge"><meta name=viewport content="width=device-width, initial-scale=1.0"><title>' . $name . ' player</title><link rel=stylesheet href=https://miaadp.github.io/stream-cloud/style.css></head><body><div class=container-fluid><div class="container justify-content-center"><div class="mt-5 d-flex justify-content-center"><div class="row shadow p-3 mt-5 rounded justify-content-center"><div class="mb-3 d-flex justify-content-center"><video id=my-video class="video-js vjs-theme-forest" controls preload=metadata width=320 height=200 data-setup={}><source src="' . $link . '" type=video/mp4></video></div><div class="mt-3 row"><div class="d-flex justify-content-center"><div class=mb-3><label for=formFile class="form-label text-white">بخش افزودن زیرنویس|Add subtitle</label><input class="form-control form-control-sm" type=file id=formFile name=fileItem accept=.srt,.vtt value=hiasfsa.srt><div class="mt-1 justify-content-center"><button class="btn btn-outline-light" type=button onclick=addSub()>آپلود|Upload</button></div></div></div></div></div></div></div></div><footer class="mt-3 text-white fixed-bottom" style=background-color:#000013><div class=container><div class=row><div class="mt-3 mt-2 d-flex justify-content-center"><p style=text-align:left;font-size:12px>Design by ' . $name . '</p></div><div class="d-flex justify-content-center"><p style=text-align:left;font-size:10px>All Rights Reserved. &copy 2021</p></div></div></div></footer></body><script src=https://miaadp.github.io/stream-cloud/js.js></script></html>';
            }
            else{
                echo 'id must be hex numeric';
            }
        }
        else {
            echo 'please insert id like this : https://example.com/player/ID';
        }
    }
    else {
        echo $type.'<br>';
        $id_hex = $type;
        echo $id_hex.'<br>';
        echo ctype_xdigit($id_hex).'<br>';
        if (ctype_xdigit($id_hex)){
            $id = hex2bin($id_hex);
            echo $id.'<br>';
            include 'madeline.php';
            $MadelineProto=new API('bot.madeline', ['app_info'=>['api_id'=>getenv('api_id'),'api_hash'=>getenv('api_hash')],'logger'=>['logger_level'=>5, 'max_size'=>5242882], 'serialization'=>['serialization_interval'=>30, 'cleanup_before_serialization'=>true]]);
            $MadelineProto->botLogin(getenv(['token']));
            
            $info = $MadelineProto->channels->getMessages(['channel' => getenv('channel_files_chat_id'), 'id' => [$id]]);
            if (isset($info['media'])){
                $from_id = $info['peer_id']['user_id'];
                $media = $info['media'];
                if (isset($org_request[3])) {
                    $user_name = trim(explode('?', $org_request[2])[0]);
                }

                if (isset($user_name) && !empty($user_name)){
                    $filename = trim($user_name);
                }
                elseif (isset($info['media']['document']['attributes'][0]['file_name'])) {
                    $filename = $info['media']['document']['attributes'][0]['file_name'];
                }
                elseif (isset($info['media']['document']['attributes'][1]['file_name'])) {
                    $filename = $info['media']['document']['attributes'][1]['file_name'];
                }
                else {
                    $filename = 'unknown';
                }

                $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
                $old_ie = (bool) preg_match('#MSIE [3-8]\.#', $ua);
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
            }
            else{
                echo 'message is not media';
            }
        }
        else{
            echo 'id must be hex numeric';
        }
    }
}
else{
    echo '<h1>Hello Babe!</h1>';
}
