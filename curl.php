<?php
namespace PMVC\PlugIn\curl;

\PMVC\l(__DIR__.'/src/curl_helper.php');

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\curl';

class curl extends \PMVC\PlugIn
{
    public function init()
    {
        $this->setDefaultAlias(new MultiCurlHelper());
    }


    private function _add($url, $function, $opts)
    {
        $oCurl = new CurlHelper();
        $oCurl->setOptions($url, $opts);
        $this->add($oCurl, $function);
        return $oCurl;
    }


    public function get($url=null, $function=null)
    {
        return $this->_add($url, $function, array());
    }

    public function put($url=null, $function=null, $params=array())
    {
        $curl_opt = array(
            CURLOPT_CUSTOMREQUEST=>'PUT',
            CURLOPT_POSTFIELDS=>http_build_query($params, '', '&')
        );
        return $this->_add($url, $function, $curl_opt);
    } 

    public function post($url=null, $function=null, $params=array())
    {
        $curl_opt = array(
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>http_build_query($params, '', '&')
        );
        return $this->_add($url, $function, $curl_opt);
    } 

    public function delete($url=null, $function=null)
    {
        $curl_opt = array(
            CURLOPT_CUSTOMREQUEST=>'DELETE'
        );
        return $this->_add($url, $function, $curl_opt);
    }

    public function options($url=null, $function=null)
    {
        $curl_opt = array(
            CURLOPT_CUSTOMREQUEST=>'OPTIONS'
        );
        return $this->_add($url, $function, $curl_opt);
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
