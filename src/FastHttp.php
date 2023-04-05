<?php

namespace angletf;


class FastHttp
{
    public null|false|\CurlHandle $curl;

    /**
     * @var FastHttpResponse|null
     */
    public ?FastHttpResponse $response;

    public array $redirectionRecord = [];

    /**
     * @var FastHttpCookie|null
     */
    public ?FastHttpCookie $fastCookie;

    /**
     * 请求对象
     * @var FastHttpRequest|null
     */
    public ?FastHttpRequest $request;

    public array $redirectionCode = [
        302, 304, 303
    ];

    public function __construct()
    {
        $this->curl = curl_init();
        $this->fastCookie = new FastHttpCookie();
    }

    public function setConfig($config): static
    {
        foreach ($config as $k => $v) {
            curl_setopt($this->curl, $k, $v);
        }
        return $this;
    }

    public function close(): void
    {
        curl_close($this->curl);
    }

    /**
     * @throws \Exception
     */
    public function send(FastHttpRequest $request): FastHttpResponse
    {
        $this->request = $request;
        $this->response = null;
        $red_count = $this->request->redirectionCount;
        $this->fastCookie->parse($this->request->headers["Cookie"] ?? "");

        //set header info
        $this->request->headers["Content-type"] = "text/plain";

        $send_header = [];

        $cnf = [
            CURLOPT_HTTPHEADER => &$send_header,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => 1,
            //The contents of the "Accept-Encoding: " header.
            // This enables decoding of the response.
            // Supported encodings are "identity", "deflate", and "gzip".
            // If an empty string,"", is set, a header containing all supported encoding types is sent.
            CURLOPT_ENCODING => '',
        ];

        //$http_header["Content-length"] = strlen($cnf[CURLOPT_POSTFIELDS]);

        do {

            $send_header = [];

            if ($this->response != null) {
                //第二次请求, 如果是重定向 则需要重置 Request Method
                $this->request->headers["Content-type"] = "text/plain";

                if (!isset($this->response->header["Location"])) {
                    throw new \Exception("no Location");
                }

                $location = trim($this->response->header["Location"][0]);
                $this->request = $this->generateLocation($location);

                $red_count--;
            }

            switch ($this->request->method) {
                case HttpMethod::JSON:
                    $this->request->headers["Content-type"] = "application/json";
                    $cnf[CURLOPT_POSTFIELDS] = json_encode($this->request->body);
                    $cnf[CURLOPT_POST] = true;
                    break;
                case HttpMethod::POST:
                    $this->request->headers["Content-type"] = "application/x-www-form-urlencoded";
                    $cnf[CURLOPT_POSTFIELDS] = http_build_query($this->request->body);
                    $cnf[CURLOPT_POST] = true;
                    break;
                case HttpMethod::GET:
                    $cnf[CURLOPT_POSTFIELDS] = "";
                    $cnf[CURLOPT_POST] = false;
                    //先拆解URL, 将数据合并到body, 将body数据转到GET头
                    if (count($this->request->body) > 0) {
                        $this->request->url = $this->request->combineUrl();
                    }
                    break;
                case HttpMethod::FILE:
                    $entry = self::postEntityFile($this->request->body);
                    $this->request->headers["Content-type"] = $entry['content_type'];
                    $this->request->headers["Content-Length"] = $entry['content_len'];
                    $cnf[CURLOPT_POSTFIELDS] = $entry['body'];
                    $cnf[CURLOPT_POST] = true;
                    break;
                default:
                    throw new \Exception("Request method error, You can set the request mode as HTTP_TYPE_POST, HTTP_TYPE_GET, HTTP_TYPE_JSON");
            }


            foreach ($this->request->headers as $k => $v) {
                $send_header[] = "{$k}: {$v}";
            }

            $this->setConfig($cnf);
            curl_setopt($this->curl, CURLOPT_URL, $this->request->url);
            $result = curl_exec($this->curl);

            $err_code = curl_errno($this->curl);

            if ($err_code !== 0) {
                throw new \Exception("Http error, error code:{$err_code}, error message:" . curl_strerror($err_code));
            }

            $http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);

            $body = substr($result, $header_size);
            $header = substr($result, 0, $header_size);
            $response_header = $this->parseHeader($header);

            $resp = new FastHttpResponse();
            $resp->code = $http_code;
            $resp->header = $response_header;
            $resp->result = $result;
            $resp->body = $body;
            $this->response = $resp;

            if (in_array($http_code, $this->redirectionCode)) {
                $this->redirectionRecord[] = [
                    'request' => $this->request->clone(),
                    'response' => $resp,
                ];
            }

        } while (in_array($http_code, $this->redirectionCode) && $red_count);

        if ($http_code === 401) {
            throw new \Exception("verification failure, HttpCode: 401, result: {$body}");
        }

        if ($http_code < 200 || $http_code > 299) {
            throw new \Exception("Http request failed, HttpCode:{$http_code}, result: {$body}");
        }

        return $resp;
    }

    public function addHeader(string $name, string $value): static
    {
        $this->request->headers[FastHttpRequest::headerFormat($name)] = trim($value);
        return $this;
    }

    public function addHeaders(array $headers): static
    {
        foreach ($headers as $k => $v) {
            $this->addHeader($k, $v);
        }
        return $this;
    }

    public function setBody($data = []): static
    {
        $this->request->body = $data;
        return $this;
    }

    public function basicAuth($userName, $password): static
    {
        $basic = base64_encode("{$userName}:{$password}");
        $this->addHeader("Authorization", "Basic {$basic}");
        return $this;
    }

    public function bearerAuth($token): static
    {
        $this->addHeader("Authorization", "Bearer {$token}");
        return $this;
    }

    public function setTimeout($ms): static
    {
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $ms);
        return $this;
    }

    //body格式
    //$body = [
    //  "name" => "file",
    //  "file_name" => "file.jpg",
    //  "mime" => "image/gif",
    //  "data" => file_get_contents('../1.jpg'),
    //  "from_data" => [
    //    "like" => "lifeng"
    //  ]
    //];
    static function postEntityFile($params): array
    {
        $boundary = uniqid();

        $post_data = "--{$boundary}\r\n";

        foreach ($params['from_data'] as $k => $v) {
            $post_data .= "Content-Disposition: form-data; name=\"{$k}\"\r\n\r\n" .
                "{$v}\r\n" .
                "--{$boundary}\r\n";
        }

        $post_data .=
            "Content-Disposition: form-data; name=\"{$params['name']}\"; filename=\"{$params['file_name']}\"\r\n" .
            "Content-Type: {$params['mime']}\r\n\r\n" .
            "{$params['data']}\r\n" .
            "--{$boundary}--\r\n";

        return [
            'content_type' => "multipart/form-data; boundary={$boundary}",
            'content_len' => strlen($post_data),
            'body' => $post_data,
        ];
    }


    /**
     * @throws \Exception
     */
    private function parseHeader(string $str_header): array
    {
        $headers = [
            // "Set-cookie" => [ "some cookie" , "some cookie", "some cookie"]
        ];

        preg_match_all('/(.*?): (.*?)\n/', $str_header, $matches);

        for ($i = 0, $len = count($matches[0]); $i < $len; $i++){
            $name = $matches[1][$i];
            $value = $matches[2][$i];

            $headers[$name][] = $value;

            if (strtolower($name) != "set-cookie"){
               continue;
            }

            $this->fastCookie->parse(trim($value));
        }

        $cookie = $this->fastCookie->combine();
        if(!empty($cookie)){
            $this->request->headers["Cookie"] = $cookie;
        }

        //对所有头部进行规范化处理
        return FastHttpRequest::headersFormat($headers);
    }

    private function generateLocation(string $location) : FastHttpRequest{
        $u1 = parse_url($location);
        if (!isset($u1['host'])) {

            $location = $this->request->urlParse["scheme"]
                . "://" . $this->request->urlParse["host"]
                . $location;
        }

        return new FastHttpRequest($location, HttpMethod::GET, [], $this->request->headers, $this->request->redirectionCount - 1);
    }
}