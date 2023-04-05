<?php


namespace angletf;


class FastHttpResponse
{
    public int $code = 0;
    public string $result = "";
    public string $body = "";
    public array $header = [
        // "Set-cookie" => [ "some cookie" , "some cookie", "some cookie"]
    ];
}