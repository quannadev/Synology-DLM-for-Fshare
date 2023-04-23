<?php
require_once 'FshareApi.php';

/**
 * Fshare DLS Search Plugin
 */
class FshareDlsSearchPlugin
{
    protected $date_time;

    private $isFolder;

    protected $api;

    /**
     */
    public function __construct()
    {
        $this->isFolder = false;
        $this->api = new FshareApi();
        $this->date_time = new \DateTime();
    }

    /**
     *
     * {@inheritDoc}
     * @see DlsPluginInterface::prepare()
     */
    public function prepare($curl, $query, $username, $password)
    {
        $this->isFolder = $this->isFshareFolderLink($query);
        if ($this->isFolder) {
            if ($username != null && $password != null) {
                $this->VerifyAccount($username, $password);
                $this->api->getFolderFiles($curl, $query);
            }
        } else {
            $searchurl = "https://api.timfshare.com/v1/string-query-search?query=%s";
            $searchurl = sprintf($searchurl, urlencode($query));
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_URL, $searchurl);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36",
                "Content-Type: application/json",
                // token is lifetime
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJuYW1lIjoiZnNoYXJlIiwidXVpZCI6IjcxZjU1NjFkMTUiLCJ0eXBlIjoicGFydG5lciIsImV4cGlyZXMiOjAsImV4cGlyZSI6MH0.WBWRKbFf7nJ7gDn1rOgENh1_doPc07MNsKwiKCJg40U'
            ));
        }

    }

    /**
     *
     * {@inheritDoc}
     * @see DlsPluginInterface::parse()
     */
    public function parse($plugin, $searchPageHtml)
    {
        $count = 0;
        if ($this->isFolder) {
            $data_array = json_decode($searchPageHtml, true);
            foreach ($data_array as $res) {
                $title = $res["name"];
                $download = $res["furl"];
                $size = (int)$this->size_format($res["size"]);
                $page = $res["furl"];
                $hash = md5($res["furl"]);
                $seeds = 0;
                $leechs = 0;
                $category = "";
                $plugin->addResult($title, $download, $size, $this->getISO8601DateTime(), $page, $hash, $seeds, $leechs, $category);
                $count++;
            }
        } else {
            $data_array = json_decode(substr($searchPageHtml, strpos($searchPageHtml, '{')), true)['data'];
            // Print the data array
            foreach ($data_array as $res) {
                $title = $res["name"];
                $download = $res["url"];
                $size = (int)$this->size_format($res["size"]);
                $page = $res["url"];
                $hash = md5($res["url"]);
                $seeds = 0;
                $leechs = 0;
                $category = "";
                $plugin->addResult($title, $download, $size, $this->getISO8601DateTime(), $page, $hash, $seeds, $leechs, $category);
                $count++;
            }
        }
        return $count;
    }

    public function size_format($sizestr)
    {
        $size_map = array(
            "KiB" => 1024,
            "MiB" => 1048576,
            "GiB" => 1073741824,
        );
        foreach ($size_map as $n => $mux) {
            if (strstr($sizestr, $n)) {
                $sizestr = floatval($sizestr) * $mux;
                break;
            }
        }
        return $sizestr;
    }

    public function getISO8601DateTime()
    {
        return $this->date_time->format(DateTime::ATOM);
    }

    function isFshareFolderLink($link)
    {
        $pattern = '/^https?:\/\/(www\.)?fshare\.vn\/folder\/[a-zA-Z0-9]+(\?token=[a-zA-Z0-9]+)?$/';
        return preg_match($pattern, $link);
    }

    public function VerifyAccount($username, $password)
    {
        if ($this->api->doLogin($username, $password) == USER_IS_PREMIUM) {
            return TRUE;
        }
        return FALSE;
    }

}

/**
 * ### For test
 */
//class FakeSynologyDLMSearchPlugin {
//    public function addResult() {
//        $arg_list = func_get_args();
//        echo join(' ', $arg_list) . "\n";
//    }
//}
//$query = "mission";
//$username = "abc@gmail.com";
//$password = "aa";
//$curl = curl_init();
//
//$client = new FshareDlsSearchPlugin();
//$client->prepare($curl, $query, $username, $password);
//$response = curl_exec($curl);
//curl_close($curl);
//$result = $client->parse(new FakeSynologyDLMSearchPlugin, $response);
//echo "Finished pulling $result results\n";

