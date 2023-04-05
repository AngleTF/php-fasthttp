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
    if ($cookie1 === null){
        echo "get cookie failure\n";
    }else{
        echo "get cookie success: $cookie1\n";
    }

    $cookie2 = $fast_cookie->getValue("_csrf");
    if ($cookie2 === null){
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


