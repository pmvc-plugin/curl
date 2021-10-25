<?php

namespace PMVC\PlugIn\curl;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\BodyDev';

class BodyDev
{
    public function __invoke($body)
    {
        $body = $this->caller->cook_body($body);
        if (isset($body['PW'])) {
            $body['PW'] = '*secret*';
        }
        return $body;
    }
}
