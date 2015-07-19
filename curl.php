<?php
namespace PMVC\PlugIn\curl;

\PMVC\l(__DIR__.'/src/curl_helper.php');

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\curl';

class curl extends \PMVC\PlugIn
{
    public function init()
    {
        $this->setDefaultAlias(new \Multi_Curl_Helper());
    }

    public function get($url=null, $function=null)
    {
        $oCurl = new \Curl_Helper();
        $oCurl->set_options($url);
        $this->add($oCurl, null, $function);
        return $oCurl;
    }

    public function put($url=null, $function=null, $params=array())
    {
        $oCurl = new \Curl_Helper();
        $curl_opt = array(
            CURLOPT_CUSTOMREQUEST=>'PUT',
            CURLOPT_POSTFIELDS=>http_build_query($params, '', '&')
        );
        $oCurl->set_options($url,$curl_opt);
        $this->add($oCurl, null, $function);
        return $oCurl;
    } 

    public function post($url=null, $function=null, $params=array())
    {
        $oCurl = new \Curl_Helper();
        $curl_opt = array(
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>$params
        );
        $oCurl->set_options($url,$curl_opt);
        $this->add($oCurl, null, $function);
        return $oCurl;
    } 

    public function delete($url=null, $function=null)
    {
        $oCurl = new \Curl_Helper();
        $curl_opt = array(
            CURLOPT_CUSTOMREQUEST=>'DELETE'
        );
        $oCurl->set_options($url,$curl_opt);
        $this->add($oCurl, null, $function);
        return $oCurl;
    }
}
