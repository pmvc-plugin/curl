<?php
namespace PMVC\PlugIn\curl;

\PMVC\l(__DIR__.'/curl_helper.php');

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\curl';

class curl extends \PMVC\PlugIn
{

    function init()
    {
        $this->setDefaultAlias(new \Multi_Curl_Helper());
    }



    function get($url=null,$function=null)
    {
        if(!is_callable($function)){
            return parent::get($path,$function);
        }
        $oCurl = new \Curl_Helper();
        $oCurl->set_options($url);
        $this->add($oCurl,null,$function);
        return $oCurl;
    }

}
?>
