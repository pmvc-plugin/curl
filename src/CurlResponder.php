<?php
namespace PMVC\PlugIn\curl;

/**
 * keep curl respone data
 */
class CurlResponder
{
    /**
     * @var http respone code
     */
    public $code;

    /**
     * @var http respone header
     */
    public $header;

    /**
     * @var http respone rawHeader 
     */
    public $rawHeader;

    /**
     * @var http respone header
     */
    public $body;

    /**
     * @var Request url
     */
    public $url;

    /**
     * @var http respone more information
     */
    public $more;

    /**
     * @var curl error code
     */
    public $errno;

    /**
     * @var prepare for purge 
     */
    public $purge;

    /**
     * @var get debug information
     */
    public $info;

    /**
     * Construct
     * 
     * !!Keep in mind!!
     * Need make data simple for json_encode and json_decode.
     * We should not use any object in attribute.
     */
    public function __construct($return, $curlHelper, $more=[])
    {
        if (empty($curlHelper)) {
            return;
        }
        $oCurl = $curlHelper->getInstance();
        $this->errno = curl_errno($oCurl);
        $this->code = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
        $this->url = curl_getinfo($oCurl, CURLINFO_EFFECTIVE_URL);
        $this->handleMore($oCurl, $more);
        $header_size = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        if (empty($header_size)) {
            return;
        }
        $this->rawHeader = substr($return, 0, $header_size);
        $header = $this->getHeaders($this->rawHeader);
        if (isset($header['multi'])) {
            $this->multiHeader = $header['multi'];
            $this->header = call_user_func_array(
              '\PMVC\arrayReplace',
              $header['multi'] 
            );
        } else {
            $this->header = $header;
        }
        if ($curlHelper->manualFollow
            && isset($this->header['location'])
        ) {
            $this->url = $this->header['location'];
            $oCurl = new CurlHelper();
            $oCurl->setOptions($this->url, function($r){
                $this->header = $r->header;
                $this->body = $r->body;
            }, $curlHelper->set());
            $oCurl->process(); 
        } elseif (!empty($this->header['content-encoding']) 
            && 'gzip' === $this->header['content-encoding']
        ) {
            $encodeBody = substr($return, $header_size+10, -8);
            if ($encodeBody) {
                $this->body = gzinflate($encodeBody);
            }
        } else {
            $this->body = substr($return, $header_size);
        }
    }

    public function handleMore($oCurl, $more)
    {
        $infoToStr = \PMVC\plug('curl')->info_to_str();
        $infos = $infoToStr->flip(curl_getinfo($oCurl));
        if (true === \PMVC\get($more,0)) {
            $more = array_keys($infos);
        }
        \PMVC\dev(function() use (&$more) {
            array_push(
                $more,
                CURLINFO_FILETIME,
                CURLINFO_TOTAL_TIME,
                CURLINFO_NAMELOOKUP_TIME,
                CURLINFO_CONNECT_TIME,
                CURLINFO_PRETRANSFER_TIME,
                CURLINFO_STARTTRANSFER_TIME,
                CURLINFO_REDIRECT_TIME
            );
            \PMVC\dev(function() use (&$more) {
                $more[]= 'request_header';
            }, 'req');
        }, 'curl');
        if (!empty($more)) {
            $more = array_unique($more);
            foreach ($more as $key) {
                $info = [];
                $info[0] = \PMVC\get($infos, $key, function() use($oCurl, $key){
                    if (is_numeric($key)) {
                        return curl_getinfo($oCurl, $key);
                    }
                });
                if ('request_header' === $key) {
                    $info[0] = [
                        'raw'=>$info[0],
                        'data'=>$this->getHeaders($info[0])
                    ];
                }
                $info[1] = $infoToStr->one($key);
                $this->more[$key] = $info;
            }
        }
    }

    /**
     * parse header String
     */
    public function getHeaders($str)
    {
        $headers = explode("\r\n", $str);
        $multi = [];
        $headerdata = [];
        foreach ($headers as $value) {
            $header = explode(": ", $value);
            if (!empty($header[0]) && !isset($header[1])) {
                if (!empty($headerdata['status'])) {
                    $multi[] = $headerdata;
                    $headerdata = [];
                }
                $headerdata['status'] = $header[0];
            } elseif (!empty($header[0]) && !empty($header[1])) {
                $key = strtolower($header[0]);
                if (empty($headerdata[$key])) {
                    $headerdata[$key] = $header[1];
                } else {
                    if (!is_array($headerdata[$key])) {
                        $headerdata[$key] = [$headerdata[$key]];
                    }
                    $headerdata[$key][] = $header[1];
                }
            }
        }
        if (!empty($multi)) {
            $multi[] = $headerdata;
            return ['multi'=>$multi];
        } else {
            return $headerdata;
        }
    }

    /**
     * purge
     */
     public function purge()
     {
        if (empty($this->purge)) {
            return;
        }
        return call_user_func($this->purge);
     }

    /**
     * debug information 
     */
     public function info()
     {
        if (empty($this->info)) {
            return;
        }
        return call_user_func($this->info);
     }

     static public function fromJson($json)
     {
        $r = new CurlResponder(false, false);
        $arr = \PMVC\fromJson($json, true);
        if (empty($arr) || !is_array($arr)) {
            return false;
        }
        foreach ($arr as $k=>$v) {
            $r->$k = $v;
        }
        $r->body = gzuncompress(urldecode($r->body));
        return $r;
     }
}
