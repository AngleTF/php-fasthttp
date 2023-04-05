## 环境要求

PHP >= 8.1

## 安装

```shell
composer require angletf/php-fasthttp
```

## 快速使用

### Cookie操作

| 类名             | 方法名                                                                       | 作用                                                   | 返回          |
|----------------|---------------------------------------------------------------------------|------------------------------------------------------|-------------|
| FastHttpCookie | parse(string $cookie)                                                     | 解析字符串cookie                                          | this        |
| FastHttpCookie | getValue(string $name)                                                    | 获取某个cookie的值, 如果没有则返回null 需要全等判断 防止有空字符的值, 如果有则返回字符串 | null/string |
| FastHttpCookie | set(string $name, string $value, string $path = "", string $expires = "") | 设置某个cookie不存在会创建, 存在会覆写                              | this        |
| FastHttpCookie | combine() | 将新的cookie生成字符串返回                                     | string      |

```php
<?php

include_once "../vendor/autoload.php";

use angletf\FastHttpCookie;

$str_cookie = "_csrf=wdCG_U13JnoO3xSUFIpLj0st; did=s%3Av0%3A12e72b20-782b-11ed-8460-7b623f70b5d3.0Fp0NKa7oimQ9mGOz%2Fee46BjSo3Ii05lN%2BaZPjv5ncw; did_compat=s%3Av0%3A12e72b20-782b-11ed-8460-7b623f70b5d3.0Fp0NKa7oimQ9mGOz%2Fee46BjSo3Ii05lN%2BaZPjv5ncw; _gid=GA1.2.2144806362.1676015793; __hstc=168269822.f20a4d48fa563e15e28b528e71ffb897.1676091214648.1676091214648.1676091214648.1; hubspotutk=f20a4d48fa563e15e28b528e71ffb897; __hssrc=1; _ga_MHX0C3C1PT=GS1.1.1676096875.9.0.1676096875.0.0.0; _ga_494MDL3VSL=GS1.1.1676096876.9.0.1676096876.60.0.0; _ga=GA1.2.1052398381.1670636426; auth0=s%3Av1.gadzZXNzaW9ugqZoYW5kbGXEQDso88oruIuB_bZwNjuUjWkWXHpUgFTUhXacqrRJ73n4Wrcoliis0TE09rmrmJYK-dFUGuxP9mYJ2mldeay6ciOmY29va2llg6dleHBpcmVz1__JdrUAY-sp8q5vcmlnaW5hbE1heEFnZc4PcxQAqHNhbWVTaXRlpG5vbmU.zE0U%2BRaWcYoQ6AFjlKOAuKuQPBxudkqnHNn%2B1pS9CtU; auth0_compat=s%3Av1.gadzZXNzaW9ugqZoYW5kbGXEQDso88oruIuB_bZwNjuUjWkWXHpUgFTUhXacqrRJ73n4Wrcoliis0TE09rmrmJYK-dFUGuxP9mYJ2mldeay6ciOmY29va2llg6dleHBpcmVz1__JdrUAY-sp8q5vcmlnaW5hbE1heEFnZc4PcxQAqHNhbWVTaXRlpG5vbmU.zE0U%2BRaWcYoQ6AFjlKOAuKuQPBxudkqnHNn%2B1pS9CtU;";

try{
    $fast_cookie = new FastHttpCookie();

    //解析cookie, 解析后可以直接通过getValue方法获取
    $fast_cookie->parse($str_cookie);
    if( $fast_cookie->combine() == $str_cookie){
        echo "cookie combine success\n";
    }else{
        echo "cookie combine failure\n";
    }

    //Get Cookie
    //获取不存在的cookie返回 NULL, 获取成功则返回对应的数据
    $cookie1 = $fast_cookie->getValue("null_data");
    if ($cookie1 == null){
        echo "get cookie failure\n";
    }else{
        echo "get cookie success: $cookie1\n";
    }

    $cookie2 = $fast_cookie->getValue("_csrf");
    if ($cookie2 == null){
        echo "get cookie failure\n";
    }else{
        echo "get cookie success: $cookie2\n";
    }

    //Set Cookie
    $cookie3 = "new_cookie";
    $fast_cookie->set("_csrf", $cookie3);

    $cookie4 = $fast_cookie->getValue("_csrf");
    if ($cookie3 != $cookie4){
        echo "set cookie failure\n";
    }else{
        echo "set cookie success: $cookie4\n";
    }

}catch (\Exception $e){
    print_r($e);
}

```

### 快速请求

| 类名             | 方法名                                                                                                                              | 作用                   | 返回               |
|----------------|----------------------------------------------------------------------------------------------------------------------------------|----------------------|------------------|
| FastHttpRequest | __construct(string $url, HttpMethod $method = HttpMethod::GET, array $body = [],array $headers = [], int $redirection_count = 0) | 构造函数                 | this             |
| FastHttpRequest | getUrlPath()                                                                                                                     | 返回去除 queryString 的部分 | string           |
| FastHttpRequest | combineUrl()                                                                                                                     | 返回完整的url             | string           |
| FastHttp | send(FastHttpRequest $request)                                                                                                                           | 发送请求                 | FastHttpResponse |
| FastHttpRequest | close()                                                                                                                     | 关闭连接                 | void             |

> 请求前需要先构造 Request, 其中 `url` 为请求地址, `method` 为请求类型为枚举类型 在RESTful 接口请求中一般为 HttpMethod::JSON,
> `body` 为请求内容, `headers` 为请求的头 数组的 key => value 形式, `redirection_count` 则为当 HttpCode 为 302, 304, 303 时的跳转次数

```php


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
```