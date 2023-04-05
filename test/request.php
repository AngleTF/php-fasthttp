<?php

include_once "../vendor/autoload.php";

use angletf\FastHttp;
use angletf\FastHttpRequest;
use angletf\HttpMethod;


try{

    //在Get方式中传递Body数据, 最终会解析至 Url的querystring, 如果重复则会进行覆盖
    $fast_request = new FastHttpRequest("http://m.baidu.com/index.php?a=234&j=OK&v=&hh=8&",
        HttpMethod::GET,        //请求方式
        ["a" => "666"],                //请求内容
        ["cooKie" => "abs=123",], 3);     //请求头部

    echo $fast_request->getUrlPath() . "\n";

    echo $fast_request->combineUrl() . "\n";

    $fast_http = new FastHttp();
    $response = $fast_http->send($fast_request);

    echo $response->body;
    echo $response->code;

    $fast_http->close();

    //header的返回会已标准格式返回, 比如 set-cookie 会被转成 Set-Cookie
    //var_dump($response->header);
}catch (\Exception $e){
    print_r($e);
}


