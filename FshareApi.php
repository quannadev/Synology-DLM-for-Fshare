<?php

class FshareApi
{
    private $api = "https://api.fshare.vn/api";
    protected $token;

    protected $AppId;
    private $COOKIE_JAR = '/tmp/fsharevn.cookie';
    private $LOG_FILE = '/tmp/fsharevn.log';
    private $TOKEN_FILE = '/tmp/fsharevn.token';

    private $session_id = "";

    public function __construct()
    {
        $this->AppId = "GUxft6Beh3Bf8qKP7GC2IplYJZz1A53JQfRwne0R";
    }

    public function doLogin($username, $password)
    {
        if(file_exists($this->COOKIE_JAR)) {
            unlink($this->COOKIE_JAR);
        }
        $ret = LOGIN_FAIL;

        $service_url = $this->api . '/user/login';
        $curl = curl_init($service_url);
        $data = array(
            "app_key" => $this->AppId,
            "password" => $password,
            "user_email" => $username
        );

        $data_string = json_encode($data);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->COOKIE_JAR);

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36",
            "Content-Type: application/json",
            'Content-Length: ' . strlen($data_string)
        ));

        $curl_response = curl_exec($curl);

        if ($this->isOK($curl, $curl_response)) {
            var_dump("login success");
            $data = json_decode($curl_response);
            $this->Token = $data->{'token'};
            $this->session_id = $data->{'session_id'};
            // save token to disk
            $this->saveToken($this->Token);
            $this->isLogin = true;
            $ret = USER_IS_PREMIUM;
            curl_close($curl);
            return $ret;
        }

        curl_close($curl);
        return $ret;
    }

    public function getToken()
    {
        if (file_exists($this->COOKIE_JAR)) {
            $myfile = fopen($this->TOKEN_FILE, "r");
            $token = fgets($myfile);
            fclose($myfile);
            $this->Token = $token;
            return $token;
        } else {
            return "";
        }
    }

    public function getFolderFiles($curl, $url)
    {
        $service_url = $this->api . '/fileops/getFolderList';
        $data = array(
            "token" => $this->Token,
            "url" => $url,
            "dirOnly" => 0,
            "pageIndex" => 0,
            "limit" => 100
        );
        $data_string = json_encode($data);
        curl_setopt($curl, CURLOPT_URL, $service_url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->COOKIE_JAR);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36",
            "Content-Type: application/json",
            'Cookie: session_id='.$this->session_id
        ));
        return curl_exec($curl);
    }

    private function saveToken($token)
    {
        $myfile = fopen($this->TOKEN_FILE, "w");
        fwrite($myfile, $token);
        fclose($myfile);
    }

    private function isOK($curl, $response)
    {
        if ($response === false) {
            return false;
        }
        $info = curl_getinfo($curl);
        if ($info['http_code'] !== 200) {
            return false;
        }
        $code = json_decode($response)->{'code'};
        if (!empty($code) && $code !== 200) {
            return false;
        }
        return true;
    }
}