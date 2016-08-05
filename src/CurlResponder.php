<?php
namespace PMVC\PlugIn\curl;

use SplFixedArray;

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
     * @var http respone more information
     */
    public $more;

    /**
     * @var curl error code
     */
    public $errno;

    /**
     * construct
     */
    public function __construct($return, $curlHelper, $more=[])
    {
        $oCurl = $curlHelper->getInstance();
        $this->errno = curl_errno($oCurl);
        $this->code = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
        if (empty($header_size)) {
            return;
        }
        $this->rawHeader = substr($return, 0, $header_size);
        $this->header = $this->getHeaders($this->rawHeader);
        if ($curlHelper->manualFollow
            && isset($this->header['location'])
        ) {
            $curlHelper->setOptions($this->header['location'], null, function($r){
                $this->body = $r->body;
            });
            $curlHelper->process(); 
        } elseif (!empty($this->header['content-encoding']) 
            && 'gzip' === $this->header['content-encoding']
        ) {
            $this->body = gzinflate(substr($return, $header_size+10, -8));
        } else {
            $this->body = substr($return, $header_size);
        }
        if (!empty($more)) {
            $pCurl = \PMVC\plug('curl');
            foreach ($more as $key) {
                $info = new SplFixedArray(2);
                $info[0] = curl_getinfo($oCurl, $key);
                $info[1] = $pCurl->infoToStr()->one($key);
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
            return $multi;
        } else {
            return $headerdata;
        }
    }
}
