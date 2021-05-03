<?php
namespace PMVC\PlugIn\curl;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\CleanJsonP';

class CleanJsonP
{
    public function __invoke($json) {
        $first = strpos($json, '{');
        $len  = strrpos($json, '}') + 1 - $first;
        return substr($json, $first, $len);
    }
}
