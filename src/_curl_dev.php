<?php

namespace PMVC\PlugIn\curl;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\CurlDev';

class CurlDev
{
    public function __invoke($r, $opts)
    {
        $rinfo = (array)$r;
        $options = $this->
            caller->
            opt_to_str()->
            all($opts);
        $url = \PMVC\plug('url')->
            getUrl($opts[CURLOPT_URL]);
        $arrUrl = \PMVC\get($url);
        $arrUrl['query'] = \PMVC\get($url->query);
        $body = $rinfo['body'];
        unset($rinfo['body']);
        if (!mb_detect_encoding($body,'utf-8',true)) {
            $body = utf8_encode($body);
        }
        $body = \PMVC\fromJson($body);
        if (isset($body->PW)) {
            $body->PW = '*secret*';
        }
        $return = [
            'option' => $options, 
            'url'    => $arrUrl,
            'respond'=> $rinfo,
            'body'   => $body,
        ];
        \PMVC\dev(
        /**
         * @help Get curl trace info.
         */
        function() use (&$return) {
            $return['where'] = \PMVC\plug('debug')->parseTrace(debug_backtrace(), 14); 
        }, 'curl-where');
        return $return;
    }
}
