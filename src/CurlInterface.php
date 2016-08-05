<?php
namespace PMVC\PlugIn\curl;

interface CurlInterface 
{
    public function setOptions(
        $url,
        callable $function=null,
        array $options=[]
    );

    public function process(
        array $more=[],
        $func='curl_exec'
    );
}
