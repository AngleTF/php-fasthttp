<?php

namespace angletf;

class FastHttpRequest
{
    public string $url = '';
    public array $urlParse = [
        "scheme" => '',
        "host" => '',
        "port" => '',
        "query" => '',
        "path" => '',
    ];


    public HttpMethod $method = HttpMethod::GET;

    public array $headers = [
        // "key" => "value"
    ];

    public array $body = [];

    public int $redirectionCount = 0;

    public function __construct(string $url, HttpMethod $method = HttpMethod::GET, array $body = [], array $headers = [], int $redirection_count = 0)
    {
        $this->urlParse = array_merge($this->urlParse, parse_url($url));
        $this->url = $url;

        $this->method = $method;
        $this->body = $body;
        $this->headers = self::headersFormat($headers);
        $this->redirectionCount = $redirection_count;
    }

    public function getUrlPath(): string
    {
        if (empty($this->urlParse['port'])) {
            return sprintf("%s://%s%s", $this->urlParse['scheme'], $this->urlParse['host'], $this->urlParse['path']);
        }

        return sprintf("%s://%s:%s%s", $this->urlParse['scheme'], $this->urlParse['host'], $this->urlParse['port'], $this->urlParse['path']);
    }

    public function combineUrl(): string
    {
        $query_string = [];
        $math_count = preg_match_all('/([^=&]*)=([^=&]*)/', $this->urlParse['query'], $math_all);
        for ($i = 0; $i < $math_count; $i++) {
            $name = $math_all[1][$i];
            $value = $math_all[2][$i];

            $query_string[$name] = $value;
        }

        $body = array_merge($query_string, $this->body);
        return $this->getUrlPath() . '?' . http_build_query($body);
    }


    /**
     * format headers
     *      content-type => Content-Type
     * @param array $header
     * @return array
     */
    static public function headersFormat(array $header = []): array
    {
        $new_header = [];
        foreach ($header as $k => $v) {
            $new_header[self::headerFormat($k)] = $v;
        }
        return $new_header;
    }

    static public function headerFormat(string $name): string
    {
        $arr_key_slice = explode("-", strtolower($name));
        foreach ($arr_key_slice as &$slice_key) {
            strlen($slice_key) > 0 && $slice_key[0] = chr(ord($slice_key[0]) & 0b1011111);
        }

        return join("-", $arr_key_slice);
    }

    public function clone(): self
    {
        return new self($this->url, $this->method, $this->body, $this->headers, $this->redirectionCount);
    }
}