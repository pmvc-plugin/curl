<?php
namespace PMVC\PlugIn\curl;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\OPT_TO_STR';

/**
 * @see https://gist.github.com/blainesch/4626922
 */
class OPT_TO_STR
{
    private $_keys = [
        CURLOPT_VERBOSE=>'VERBOSE',
        CURLOPT_HEADER=>'HEADER',
        CURLOPT_FOLLOWLOCATION=>'FOLLOWLOCATION',
        CURLOPT_SSL_VERIFYPEER=>'SSL_VERIFYPEER',
        CURLOPT_CONNECTTIMEOUT=>'CONNECTTIMEOUT',
        CURLOPT_SSL_VERIFYHOST=>'SSL_VERIFYHOST',
        CURLOPT_RETURNTRANSFER=>'RETURNTRANSFER',
        CURLOPT_URL=>'URL',
        CURLOPT_USERAGENT=>'USERAGENT',
        CURLOPT_FAILONERROR=>'FAILONERROR'
    ];
    
    function __invoke()
    {
        return $this;
    }

    function all(array $opts)
    {
        $return = [];
        foreach($opts as $k=>$v){
            $return[$this->one($k)] = $v;
        }
        return $return;
    }

    function one($k)
    {
        if (isset($this->_keys[$k])) {
            return $this->_keys[$k];
        } else {
            return $k;
        }
    }
}
