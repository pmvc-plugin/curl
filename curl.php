<?php

namespace PMVC\PlugIn\curl;

use PMVC\HashMap;

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

    private function _add($url, $function, $opts, $ignore=null)
    {
        $oCurl = new CurlHelper();
        $oCurl->setOptions($url, $function, $opts);
        if (!$ignore) {
            $this->add($oCurl);
        }
        return $oCurl;
    }

    private function _getUrl($url, $querys)
    {
        if (!empty($querys)) {
            $url = \PMVC\plug('url')->getUrl($url);
            $url->query->set($querys);
        }
        return $url;
    }

    private function _toJson (&$opt)
    {
        $opt[CURLOPT_POSTFIELDS] = json_encode($opt[CURLOPT_POSTFIELDS]);
        $opt[CURLOPT_HTTPHEADER] = array_merge(
            \PMVC\get($opt, CURLOPT_HTTPHEADER, []),
            [
                'Content-Type: application/json',
                'Content-Length: '.strlen($opt[CURLOPT_POSTFIELDS])
            ]
        );
    }

    public function handleCookie($url=null, $function=null, array $querys=[])
    {
        if (!isset($this['cookieHandler'])) {
            $this['cookieHandler'] = new HashMap();
        }
        $url = $this->_getUrl($url, $querys);
        $ocurl = $this->_add($url, $function, [], true);
        $this['cookieHandler'][] = $ocurl;
        return $ocurl;
    }

    public function getCookie(CurlResponder $responder)
    {
        $setCookie = \PMVC\get($responder->header, 'set-cookie'); 
        $options = [
            CURLOPT_COOKIE=>
                join(
                    '; ',
                    \PMVC\plug('cookie')->parseSetCookieString($setCookie)
                )
        ];
        return $options;
    }

    /**
     * Take a run at destruct for some not run task 
     * http://stackoverflow.com/questions/230245/destruct-visibility-for-php
     */
    public function __destruct()
    {
        $curls = $this->getCurls();
        if (empty($curls) || !count($curls)) {
            return;
        }
        $this->process();
    }

    public function delete($url=null, $function=null, array $querys=[])
    {
        $url = $this->_getUrl($url, $querys);
        $curl_opt = array(
            CURLOPT_CUSTOMREQUEST=>'DELETE'
        );
        return $this->_add($url, $function, $curl_opt);
    }

    public function get($url=null, $function=null, array $querys=[])
    {
        $url = $this->_getUrl($url, $querys);
        return $this->_add($url, $function, array());
    }

    public function options($url=null, $function=null, array $querys=[])
    {
        $url = $this->_getUrl($url, $querys);
        $curl_opt = array(
            CURLOPT_CUSTOMREQUEST=>'OPTIONS'
        );
        return $this->_add($url, $function, $curl_opt);
    }

    /**
     * @params string   $url                  Request Url
     * @params callable $function             Respond callback function
     * @params array    $querys               Parameters
     * @params bool     $useMultiPartFormData If Upload file set this to true
     * @params bool     $json                 Use json format
     */
    public function post($url=null, $function=null, array $querys=[], $useMultiPartFormData=false, $json=false)
    {
        if (!$useMultiPartFormData) {
            // for non-upload file case
            if (!$json) {
                $querys = http_build_query($querys, '', '&');
            }
        }
        $curl_opt = [ 
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>$querys,
        ];
        if ($json) {
            $this->_toJson($curl_opt);
        }
        return $this->_add($url, $function, $curl_opt);
    }

    public function put($url=null, $function=null, array $querys=[], $json=false)
    {
        if (!$json) {
            $querys = http_build_query($querys, '', '&');
        }
        $curl_opt = [ 
            CURLOPT_CUSTOMREQUEST=>'PUT',
            CURLOPT_POSTFIELDS=>$querys
        ];
        if ($json) {
            $this->_toJson($curl_opt);
        }
        return $this->_add($url, $function, $curl_opt);
    } 
}
