<?php
ini_set('max_execution_time',-1);
ini_set('max_input_time',-1);
ini_set('max_file_uploads',-1);
set_time_limit(24*3600);
error_reporting(0);
function retrieve_remote_file_size($url){
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_NOBODY, TRUE);
    $data = curl_exec($ch);
    preg_match_all('/filename="(.*?)"/', $data, $name);
    file_put_contents('data',json_encode($name));
    $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    curl_close($ch);
    return ['size'=>$size,'name'=>$name[1][0]];
}
function send_attachment ($filename, $server_filename, $expires = 0, $speed_limit = 0) {
    $remote = false;
    if (strpos($server_filename,'dl_')===0){
        //$server_filename = "http://bobow.farahost.xyz/down/bot.php?id=$server_filename";
    }
    if (strpos($server_filename, 'http') === false) {
        if (!file_exists($server_filename) || !is_readable($server_filename)) {
            return false;
        }
        if (($filesize = filesize($server_filename)) == 0) {
            return false;
        }
        if (($fp = @fopen($server_filename, 'rb')) === false) {
            return false;
        }
    }
    else {
        $info = retrieve_remote_file_size($server_filename);
        $filename = !is_null($info['name'])?$info['name']:$filename;
        $filesize = $info['size'];
        $remote = true;
        $ch = curl_init($server_filename);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//        if ($speed_limit > 0){
//            curl_setopt($ch, CURLOPT_MAX_RECV_SPEED_LARGE, $speed_limit);
//        }
        curl_setopt($ch, CURLOPT_HTTPHEADER,array(
            'Cookie: .AUSHELLPORTAL=3A5478AFE7BC5048A0D46168410041D33115263C9D910734ACD034C18EEF87E1D8F254E4818BAE0769096E78A5B6C0E70FB282474D187443073A65FF805D6C5A4A2FEBEA08106EA7A34A81B61CFA269DFF55016D88E7724446232025FC9C9722D6D71D7F73A49D3B0B44B148487FC8F52F5192C015E988F680CA14FFC33E2C3FCCA76F752B026C378C8CDF90C2BC062CA4717B9E941858631D7E05784834D7881A76B5A7CCE3C0C7D3F507B68063D82ADDC89D206B9E37E6F3710332197A92A13C82C89F; .AUSHELLPORTALws=3A5478AFE7BC5048A0D46168410041D33115263C9D910734ACD034C18EEF87E1D8F254E4818BAE0769096E78A5B6C0E70FB282474D187443073A65FF805D6C5A4A2FEBEA08106EA7A34A81B61CFA269DFF55016D88E7724446232025FC9C9722D6D71D7F73A49D3B0B44B148487FC8F52F5192C015E988F680CA14FFC33E2C3FCCA76F752B026C378C8CDF90C2BC062CA4717B9E941858631D7E05784834D7881A76B5A7CCE3C0C7D3F507B68063D82ADDC89D206B9E37E6F3710332197A92A13C82C89F; iloc=6Bmi/lsWXSsvNoNswQ6jqeBpEuLxmZhC/EIEw8jbOlU=; retkeyapi=636F7A4C456276737269453649687433764C31305834692B78556C736F414138',
        ));
    }

    $pass_remote = function ($ch, $chunk) {
        echo $chunk;
        flush();
        return strlen($chunk);
    };

    // replace special characters
    $illegal = ['\\', '/', '<', '>', '{', '}', ':', ';', '|', '"', '~', '`', '@', '#', '$', '%', '^', '&', '*', '?'];
    $replace = ['', '', '(', ')', '(', ')', '_', ',', '_', '', '_', '\'', '_', '_', '_', '_', '_', '_', '', ''];
    $filename = str_replace($illegal, $replace, $filename);
    $filename = preg_replace('/([\\x00-\\x1f\\x7f\\xff]+)/', '', $filename);
    // replace special spaces to normal spaces(0x20).
    $filename = trim(preg_replace('/[\\pZ\\pC]+/u', ' ', $filename));
    // remove duplicates or dots.
    $filename = trim($filename, ' .-_');
    $filename = preg_replace('/__+/', '_', $filename);
    if ($filename === '') {
        return false;
    }

    // get User-Agent from browser
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $old_ie = (bool)preg_match('#MSIE [3-8]\.#', $ua);
    // add filename to header when filename only includes normal characters.
    if (preg_match('/^[a-zA-Z0-9_.-]+$/', $filename)) {
        $header = 'filename="' . $filename . '"';
    }
    // < IE 9 or < FF 5
    elseif ($old_ie || preg_match('#Firefox/(\d+)\.#', $ua, $matches) && $matches[1] < 5) {
        $header = 'filename="' . rawurlencode($filename) . '"';
    }
    // < Chrome 11
    elseif (preg_match('#Chrome/(\d+)\.#', $ua, $matches) && $matches[1] < 11) {
        $header = 'filename=' . $filename;
    }
    // < Safari 6
    elseif (preg_match('#Safari/(\d+)\.#', $ua, $matches) && $matches[1] < 6) {
        $header = 'filename=' . $filename;
    }
    // Android
    elseif (preg_match('#Android #', $ua, $matches)) {
        $header = 'filename="' . $filename . '"';
    }
    // other browsers assume that validate RFC/2231/5987 standards
    // but, add old style filename information for special circumstances
    else {
        $header = "filename*=UTF-8''" . rawurlencode($filename) . '; filename="' . rawurlencode($filename) . '"';
    }

    // process range header for resume download
    if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d+)-/', $_SERVER['HTTP_RANGE'], $matches)) {
        $range_start = $matches[1];
        if ($range_start < 0 || $range_start > $filesize) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            return false;
        }
        header('HTTP/1.1 206 Partial Content');
        header('Content-Range: bytes ' . $range_start . '-' . ($filesize - 1) . '/' . $filesize);
        header('Content-Length: ' . ($filesize - $range_start));
        if ($remote) {
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, $pass_remote);
            curl_setopt($ch, CURLOPT_RANGE, $range_start . '-' . ($filesize - 1));
        }
    }
    else {
        $range_start = 0;
        header('Content-Length: ' . $filesize);
        if ($remote) {
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, $pass_remote);
            curl_setopt($ch, CURLOPT_RANGE, '0-' . $filesize);
        }
    }
    // send other headers.
    header('Accept-Ranges: bytes');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; ' . $header);
    // clear output buffer.
    // (blocks file broken and decrease memory usage)
    while (ob_get_level()) {
        ob_end_clean();
    }
    // send a file each 64KB and clear output buffer.
    // sometimes occurs memory leak when use readfile() function.
    $block_size = 16 * 1024;
    //$speed_sleep = $speed_limit > 0 ? round(($block_size / $speed_limit / 1024) * 1000000) : 0;
    if ($range_start > 0 && !$remote) {
        fseek($fp, $range_start);
        $alignment = (ceil($range_start / $block_size) * $block_size) - $range_start;
        if ($alignment > 0) {
            $buffer = fread($fp, $alignment);
            echo $buffer;
            unset($buffer);
            flush();
        }
    }
    while (!$remote && !feof($fp)) {
        $buffer = fread($fp, $block_size);
        echo $buffer;
        unset($buffer);
        flush();
        //usleep($speed_sleep);
    }
    if ($remote && $ch) {
        curl_exec($ch);
    }
    if (!$remote) {
        fclose($fp);
    }
    else {
        curl_close($ch);
    }
    // true when successfully sent.
    return true;
}
send_attachment(basename($_GET['link']),$_GET['link']);
