<?php
namespace PMVC\PlugIn\curl;
use SplFixedArray;
use SplObjectStorage;

interface CurlInterface 
{
    public function setOptions($url, callable $function=null, array $options=[]);
    public function process($more=[]);
}

/**
* Curl helper library 
*/
class CurlHelper implements CurlInterface
{
    /**
     * @var custom follow location
     */
    public $manualFollow = false;

    /**
     * @var function 
     */
    private $_function;

    /**
     * @var default curl options
     */
    private $_opts = [];

    /**
     * @var curl resource
     */
    private $_oCurl = null;

    /**
    * Set curl option
    *
    * @param  string $url
    * @param  array  $options curl option
    * @see    http://php.net/manual/en/function.curl-setopt.php
    * @see    https://github.com/bagder/curl/blob/master/docs/libcurl/symbols-in-versions
    * @return curl_setopt_array result
    */
    public function setOptions(
        $url, 
        callable $function=null, 
        array $options=[]
    ) {
        $this->_opts = $this->getDefaultOptions();
        $options[CURLOPT_URL]=$url;
        $this->_function = $function;
        return $this->set($options);
    }

    /**
     * @param array $options curl option
     */
    public function set($options = null)
    {
        //assing custom options
        if (is_array($options)) {
            foreach ($options as $k=>$v) {
                $this->_opts[$k] = $v;
            }
        }
        return $this->_opts;
    }

    /**
     * Get default options
     */
    public function getDefaultOptions()
    {
        return [ 
             CURLOPT_HEADER         => true
            ,CURLOPT_VERBOSE        => false
            ,CURLOPT_RETURNTRANSFER => true
            ,CURLOPT_FOLLOWLOCATION => true
            ,CURLOPT_SSL_VERIFYHOST => false
            ,CURLOPT_SSL_VERIFYPEER => false
        ];
    }

    /**
     * Set manual follow location
     */
    public function setManualFollow()
    {
        $isNotGet = ( 
            ( !empty($this->_opts[CURLOPT_CUSTOMREQUEST])            
              && 'GET' !== $this->_opts[CURLOPT_CUSTOMREQUEST] 
            )
            || !empty($this->_opts[CURLOPT_POST]) 
        );
        if ( $isNotGet
            && !empty($this->_opts[CURLOPT_FOLLOWLOCATION])
        ) {
            $this->set([CURLOPT_FOLLOWLOCATION=>false]);
            $this->manualFollow = true;
        }
    }

    /**
     * Return curl execute result
     *
     * @return CurlResponder
     */
    public function process($more=[], $func='curl_exec')
    {
        $oCurl = $this->getInstance();
        if (!$oCurl) {
            return !trigger_error('Not set any CURL option yet.');
        }
        $return = call_user_func($func,$oCurl);
        $r = new CurlResponder($return, $this, $more);
        if (is_callable($this->_function)) {
            call_user_func($this->_function, $r, $this);
        }
        $this->clean();
        return true;
    }

    /**
     * @return curl resource
     */
    public function getInstance()
    {
        if (empty($this->_opts)) {
            return false;
        }
        if (is_null($this->_oCurl)) {
            $this->_oCurl = curl_init();
            curl_setopt_array($this->_oCurl, $this->_opts);
            $this->setManualFollow();
        }
        return $this->_oCurl;
    }

    /**
     * @return callback
     */
     public function getCallback()
     {
        return $this->_function;
     }

    /**
     * Reset  curl resource
     */
    public function clean()
    {
        if (is_resource($this->_oCurl)) {
            curl_close($this->_oCurl);
        }
        $this->_oCurl = null;
        $this->_opts = [];
    }

    public function __destruct()
    {
        $this->clean();
    }
}


/**
 * implement for multi curl
 */
class MultiCurlHelper
{
    /**
     * @var array CurlHelper
     */
    private $_curls;

    public function __construct()
    {
       $this->_curls = new SplObjectStorage();
    }

    /**
     * add CurlHelper to Multi_Curl_Helper
     *
     * @param $ocurl
     * @param $key a hashmap for CurlHelper, need assign key, the return result will reference
     */
    public function add($ocurl)
    {
        $this->_curls->attach($ocurl);
    }

    /**
     * Get Curl map
     */
     public function getCurls()
     {
        return $this->_curls;
     }

    /**
     * Get Curl map
     */
     public function clean()
     {
        $this->_curls->removeAll($this->_curls);
     }


    /**
     * Execute multi curl
     *
     * @return bool 
     */
    public function process($more=[])
    {
        if (empty($this->_curls)) {
            return false;
        }
        $multiCurl = curl_multi_init();
        $this->_curls->rewind();
        while ($this->_curls->valid()) {
            $obj = $this->_curls->current();
            $this->_curls->next();
            $oCurl = $obj->getInstance();
            if (!empty($oCurl)) {
                curl_multi_add_handle($multiCurl, $oCurl);
            } else {
                $this->_curls->detach($obj);
            }
        }
        if (empty($this->_curls)) {
            curl_multi_close($multiCurl);
            return false;
        }
        $curls = clone $this->_curls;
        $this->clean();
        // set run flag
        $running = null;
        do {
            $multiExec = curl_multi_exec($multiCurl, $running);
        } while ($multiExec === CURLM_CALL_MULTI_PERFORM);
        while ($running && $multiExec === CURLM_OK) {
            if (curl_multi_select($multiCurl) == -1) {
                usleep(50000);
            }
            do {
                $multiExec = curl_multi_exec(
                    $multiCurl, 
                    $running
                );
            } while ($multiExec == CURLM_CALL_MULTI_PERFORM);
        }
        foreach ($curls as $obj) {
            $obj->process($more, function($oCurl) use ($multiCurl){
                $return = curl_multi_getcontent($oCurl);
                curl_multi_remove_handle($multiCurl, $oCurl);
                return $return;
            });
        }
        curl_multi_close($multiCurl);
        return true;
    }
}


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
            foreach ($more as $key) {
                $info = new SplFixedArray(2);
                $info[0] = curl_getinfo($oCurl, $key);
                $info[1] = CurlInfo::getKey($key);
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

class CurlInfo
{
    /**
     * @see    http://php.net/manual/en/function.curl-getinfo.php
     */
    static function getKey($key)
    {
        $arr = [ 
        CURLINFO_EFFECTIVE_URL=>'EFFECTIVE_URL',
        CURLINFO_HTTP_CODE=>'HTTP_CODE',
        CURLINFO_FILETIME=>'FILETIME',
        CURLINFO_TOTAL_TIME=>'TOTAL_TIME',
        CURLINFO_NAMELOOKUP_TIME=>'NAMELOOKUP_TIME',
        CURLINFO_CONNECT_TIME=>'CONNECT_TIME',
        CURLINFO_PRETRANSFER_TIME=>'PRETRANSFER_TIME',
        CURLINFO_STARTTRANSFER_TIME=>'STARTTRANSFER_TIME',
        CURLINFO_REDIRECT_COUNT=>'REDIRECT_COUNT',
        CURLINFO_REDIRECT_TIME=>'REDIRECT_TIME',
        CURLINFO_SIZE_UPLOAD=>'SIZE_UPLOAD',
        CURLINFO_SIZE_DOWNLOAD=>'SIZE_DOWNLOAD',
        CURLINFO_SPEED_DOWNLOAD=>'SPEED_DOWNLOAD',
        CURLINFO_SPEED_UPLOAD=>'SPEED_UPLOAD',
        CURLINFO_HEADER_SIZE=>'HEADER_SIZE',
        CURLINFO_HEADER_OUT=>'HEADER_OUT',
        CURLINFO_REQUEST_SIZE=>'REQUEST_SIZE',
        CURLINFO_SSL_VERIFYRESULT=>'SSL_VERIFYRESULT',
        CURLINFO_CONTENT_LENGTH_DOWNLOAD=>'CONTENT_LENGTH_DOWNLOAD',
        CURLINFO_CONTENT_LENGTH_UPLOAD=>'CONTENT_LENGTH_UPLOAD',
        CURLINFO_CONTENT_TYPE=>'CONTENT_TYPE',
        CURLINFO_PRIVATE=>'PRIVATE'
        ];
        if (isset($arr[$key])) {
            return $arr[$key];
        } else {
            return null;
        }
    }
}
