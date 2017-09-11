<?php

namespace PMVC\PlugIn\curl;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\CurlDev';

class CurlDev
{
    public function __invoke($r, $opts)
    {
        $options = $this->
            caller->
            opt_to_str()->
            all($opts);
        $url = \PMVC\plug('url')->
            getUrl($opts[CURLOPT_URL]);
        $arrUrl = \PMVC\get($url);
        $arrUrl['query'] = \PMVC\get($url->query);
        return [
            'option' => $options, 
            'url'    => $arrUrl,
            'respond'=> $r,
            'body'   => \PMVC\fromJson($r->body)
        ];
    }
}
