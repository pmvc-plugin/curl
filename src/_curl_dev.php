<?php

namespace PMVC\PlugIn\curl;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__ . '\CurlDev';

class CurlDev
{
    public function __invoke($r, $opts)
    {
        $rinfo = (array) $r;
        $options = $this->caller->opt_to_str()->all($opts);
        $url = \PMVC\plug('url')->getUrl($opts[CURLOPT_URL]);
        $arrUrl = \PMVC\get($url);
        $arrUrl['query'] = \PMVC\get($url->query);
        $body = $this->caller->body_dev($rinfo['body']);
        unset($rinfo['body'], $rinfo['info']);
        $result = [
            '-url' => (string) $url,
            'urlObj' => $arrUrl,
            'option' => $options,
            'respond' => $rinfo,
            'body' => $body,
            'help' => [
                'trace' => 'get trace info',
                'req' => 'get request information',
            ],
        ];

        return $result;
    }
}
