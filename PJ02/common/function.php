<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/5/16
 * Time: 11:59
 */
function wLog($name, $message)
{
    if (is_scalar($message)) {
        $raw = $message;
    } else {
        $raw = json_encode($message, JSON_UNESCAPED_UNICODE);
    }

    file_put_contents('logs/'.$name . '.log', date('Y-m-d H:i:s') . ' ' . $raw . PHP_EOL, FILE_APPEND);
}

function ajaxReturn($response, $data, $encode = true) {
    $response->header('Content-Type', 'application/json; charset=utf-8');
    if ($encode == false) {
        $response->end(json_encode($data, JSON_UNESCAPED_UNICODE));
    } else {
        $response->end(json_encode($data));
    }
}

// isset三元函数
function ist($arr, $key, $default)
{
    if (!is_array($arr)) {
        return $default;
    }
    if (isset($arr[$key])) {
        return $arr[$key];
    } else {
        return $default;
    }
}

function CurlSend($url, $method, $body = [], $header = [], $timeout = 60, $cookie = ''){
    // wLog('comm.function.curl', $url);
    $rHeaders = [
        // 'Pragma: no-cache',
    ];
    $rHeaders = array_merge($header, $rHeaders);

    if (is_array($body)) {
        $body = http_build_query($body);
    }
    $rBody = $body;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $rHeaders);

    if (strtoupper($method) == 'POST') {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $rBody);
    }

    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

    if ($cookie != '') {
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    $t1 = microtime(true);
    $retbody = curl_exec($curl);
    $t2 = microtime(true);

    $extime = round(($t2 - $t1) * 1000, 0);
    $curlError = curl_error($curl);
    $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close($curl);

    // var_dump($url);
    // var_dump($method);
    // var_dump($rHeaders);
    // var_dump($rBody);
    // var_dump($retbody);
    // var_dump($httpCode);
    // die();

    if ($retbody === false) {
        return ['error' => $curlError];
    }
    list($retheader, $retbody) = explode("\r\n\r\n", $retbody, 2);

    return [
        'status' => $httpCode,
        'extime' => $extime,
        'header' => $retheader,
        'raw'    => $retbody,
    ];
}