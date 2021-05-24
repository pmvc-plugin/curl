<?php

namespace PMVC\PlugIn\curl;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\CookBody';

class CookBody
{
    public function __invoke($body)
    {
        $body = $this->caller->cleanJsonP($body);
        $body = \PMVC\plug('utf8')->toUtf8($body);
        $body = \PMVC\fromJson($body, true);
        return $body;
    }
}
