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
        if (!mb_detect_encoding($rinfo['body'],'utf-8',true)) {
            $rinfo['body'] = utf8_encode($rinfo['body']);
        }
        return [
            'option' => $options, 
            'url'    => $arrUrl,
            'respond'=> $rinfo,
            'body'   => \PMVC\fromJson($rinfo['body'])
        ];
    }
}
