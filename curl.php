<?php
namespace PMVC\PlugIn\curl;

\PMVC\l(__DIR__.'/src/CurlInterface.php');
\PMVC\l(__DIR__.'/src/CurlHelper.php');
\PMVC\l(__DIR__.'/src/CurlResponder.php');
\PMVC\l(__DIR__.'/src/MultiCurlHelper.php');

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\curl';

class curl extends \PMVC\PlugIn
{
    public function init()
    {
        $this->setDefaultAlias(new MultiCurlHelper());
    }

    private function _add($url, $function, $opts)
    {
        if (!empty($this['session'])) {
            $oCurl = $this['session'];
            unset($this['session']);
        } else {
            $oCurl = new CurlHelper();
        }
        $oCurl->setOptions($url, $function, $opts);
        $this->add($oCurl);
        return $oCurl;
    }

    private function _getUrl($url, $querys)
    {
        $url = \PMVC\plug('url')->getUrl($url);
        $url->query->set($querys);
        return $url;
    }

    public function get($url=null, $function=null, $querys=[])
    {
        $url = $this->_getUrl($url, $querys);
        return $this->_add($url, $function, array());
    }

    public function put($url=null, $function=null, $querys=[])
    {
        $curl_opt = array(
            CURLOPT_CUSTOMREQUEST=>'PUT',
            CURLOPT_POSTFIELDS=>http_build_query($querys, '', '&')
        );
        return $this->_add($url, $function, $curl_opt);
    } 

    public function post($url=null, $function=null, $querys=[], $useMultiPartFormData=false)
    {
        if (!$useMultiPartFormData) {
            // for non-upload file case
            $querys = http_build_query($querys, '', '&');
        }
        $curl_opt = array(
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>$querys
        );
        return $this->_add($url, $function, $curl_opt);
    } 

    public function delete($url=null, $function=null, $querys=[])
    {
        $url = $this->_getUrl($url, $querys);
        $curl_opt = array(
            CURLOPT_CUSTOMREQUEST=>'DELETE'
        );
        return $this->_add($url, $function, $curl_opt);
    }

    public function options($url=null, $function=null, $querys=[])
    {
        $url = $this->_getUrl($url, $querys);
        $curl_opt = array(
            CURLOPT_CUSTOMREQUEST=>'OPTIONS'
        );
        return $this->_add($url, $function, $curl_opt);
    }

    public function getCookie(CurlResponder $responder)
    {
        return [
            CURLOPT_COOKIE=>
                join(
                    ';',
                    $responder->header['set-cookie']
                )
        ];
    }

    /**
     * Take a run at destruct for some not run task 
     * http://stackoverflow.com/questions/230245/destruct-visibility-for-php
     */
    public function __destruct()
    {
        $this->process();
    }
}
