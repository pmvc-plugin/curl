<?php
namespace PMVC\PlugIn\curl;
use SplFixedArray;

interface CurlInterface 
{
    public function setOptions($url, $options=array());
    public function process($more=array());
}

/**
* a curl wraper for ci
*/
class CurlHelper implements CurlInterface
{
    /**
     * @var custom follow location
     */
    public $manualFollow = false;

    /**
     * @var default curl options
     */
    private $_opts = array();

    /**
     * @var curl resource
     */
    private $_oCurl = null;

    /**
    * set curl option
     *
    * @param  string $url
    * @param  array  $options curl option
    * @see    http://php.net/manual/en/function.curl-setopt.php
    * @see    https://github.com/bagder/curl/blob/master/docs/libcurl/symbols-in-versions
    * @return curl_setopt_array result
    */
    public function setOptions($url, $options=array())
    {
        $this->_opts = $this->getDefaultOptions();
        $options[CURLOPT_URL]=$url;
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
     * get default options
     */
    public function getDefaultOptions()
    {
        return array(
             CURLOPT_HEADER         => true
            ,CURLOPT_VERBOSE        => false
            ,CURLOPT_RETURNTRANSFER => true
            ,CURLOPT_FOLLOWLOCATION => true
            ,CURLOPT_SSL_VERIFYHOST => false
            ,CURLOPT_SSL_VERIFYPEER => false
        );
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
            $this->set(array(CURLOPT_FOLLOWLOCATION=>false));
            $this->manualFollow = true;
        }
    }

    /**
     * return curl execute result
     *
     * @see    http://php.net/manual/en/function.curl-getinfo.php
     * @return CurlResponder
     */
    public function process($more=array())
    {
        $this->setManualFollow();
        $oCurl = $this->getInstance();
        $return = curl_exec($oCurl);
        $r = new CurlResponder($return, $this, $more);
        $this->clean();
        return $r;
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
        }
        return $this->_oCurl;
    }

    /**
     * reset  curl resource
     */
    public function clean()
    {
        if (is_resource($this->_oCurl)) {
            curl_close($this->_oCurl);
        }
        $this->_oCurl = null;
        $this->_opts = array();
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
    private $_curls = array();

    /**
     * add CurlHelper to Multi_Curl_Helper
     *
     * @param $ocurl
     * @param $key a hashmap for CurlHelper, need assign key, the return result will reference
     */
    public function add($ocurl, $function=null)
    {
        $data = (object)array(
            'obj'=>$ocurl,
            'func'=>$function
        );
        $this->_curls[]=$data;
    }

    /**
     * Get Curl map
     */
     public function getCurls()
     {
        return $this->_curls;
     }

    /**
     * Take a run at destruct for some not run task 
     * http://stackoverflow.com/questions/230245/destruct-visibility-for-php
     */
    public function __destruct()
    {
        $this->process();
    }

    /**
     * execute multi curl
     *
     * @return hasmap by CurlResponder
     */
    public function process($more=array())
    {
        if (empty($this->_curls) || !is_array($this->_curls)) {
            return false;
        }
        $multiCurl = curl_multi_init();
        foreach ($this->_curls as $hash=>$data) {
            $data->obj->setManualFollow();
            $oCurl = $data->obj->getInstance();
            if ($oCurl) {
                curl_multi_add_handle($multiCurl, $oCurl);
            } else {
                unset($this->_curls[$hash]);
            }
        }
        if (empty($this->_curls)) {
            curl_multi_close($multiCurl);
            return false;
        }
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
        foreach ($this->_curls as $hash=>$data) {
            $oCurl = $data->obj->getInstance();
            $return = curl_multi_getcontent($oCurl);
            $r = new CurlResponder($return, $data->obj, $more);
            if (is_callable($data->func)) {
                call_user_func($data->func, $r);
            }
            curl_multi_remove_handle($multiCurl, $oCurl);
        }
        curl_multi_close($multiCurl);
        $this->_curls = array();
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
    public function __construct($return, $curlHelper, $more=array())
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
            $location = new  CurlHelper();
            $location->setOptions($this->header['location']);
            $respondLocation = $location->process(); 
            $this->body = $respondLocation->body;
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
                $info[0] = CurlInfo::getKey($key);
                $info[1] = curl_getinfo($oCurl, $key);
                $this->more[$key] = $info;
            }
        }
    }

    /**
     * parse header String
     */
    public function getHeaders($str)
    {
        $headers = str_replace("\r", "", $str);
        $headers = explode("\n", $headers);
        $headerdata = array();
        foreach ($headers as $value) {
            $header = explode(": ", $value);
            if (!empty($header[0]) && empty($header[1])) {
                $headerdata['status'] = $header[0];
            } elseif (!empty($header[0]) && !empty($header[1])) {
                $headerdata[strtolower($header[0])] = $header[1];
            }
        }
        return $headerdata;
    }
}

class CurlInfo
{
    static function getKey($key)
    {
        $arr = array(
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
        CURLINFO_REDIRECT_URL=>'REDIRECT_URL',
        CURLINFO_PRIMARY_IP=>'PRIMARY_IP',
        CURLINFO_PRIMARY_PORT=>'PRIMARY_PORT',
        CURLINFO_LOCAL_IP=>'LOCAL_IP',
        CURLINFO_LOCAL_PORT=>'LOCAL_PORT',
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
        );
        if (isset($arr[$key])) {
            return $arr[$key];
        } else {
            return null;
        }
    }
}
