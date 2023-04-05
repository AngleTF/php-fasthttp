<?php


namespace angletf;


class FastHttpCookie
{
    public array $cookie = [
        //"cookie-MD5" => ["name" => "", "value" => "",  "path" => "", "expires" => ""]
    ];

    private array $defaultCookie = [
        "name" => "",
        "value" => "",
        "path" => "",
        "expires" => ""
    ];

    public function set(string $name, string $value, string $path = "", string $expires = ""): static
    {
        $new_cookie = array_merge($this->defaultCookie, [
            "name" => $name,
            "value" => $value,
            "path" => $path,
            "expires" => $expires
        ]);

        $key = md5($name);

        $this->cookie[$key] = $new_cookie;
        return $this;
    }

    public function combine(): string
    {
        $str_cookie = "";
        foreach ($this->cookie as $k => $v) {
            //Cookie_1=value; Path=/; Expires=Mon, 12 Feb 2024 20:20:16 GMT;
            $str_cookie_row = "{$v["name"]}={$v["value"]}; ";
            if (!empty($v["path"])) {
                $str_cookie_row .= "Path={$v["path"]}; ";
            }
            if (!empty($v["expires"])) {
                $str_cookie_row .= "Expires={$v["expires"]};";
            }
            $str_cookie .= $str_cookie_row;
        }
        return trim($str_cookie);
    }

    /**
     * @throws \Exception
     */
    public function parse($str_cookie): static
    {

        if (empty($str_cookie)) return $this;

        $pattern = "/([\w]+)=([^;]*);?\s*(?:Path=([^;]*);)?\s*(?:Expires=([^;]*);)?/i";
        $match_count = preg_match_all($pattern, $str_cookie, $match);
        if ($match_count == false || count($match) < 5) {
            throw new \Exception("match miss");
        }

        for ($i = 0, $len = count($match[0]); $i < $len; $i++) {
            $this->set($match[1][$i], $match[2][$i], $match[3][$i], $match[4][$i]);
        }

        return $this;
    }

    public function getValue($name): ?string {
        $key = md5($name);
        if (!isset($this->cookie[$key])){
            return null;
        }
        return $this->cookie[$key]["value"];
    }
}