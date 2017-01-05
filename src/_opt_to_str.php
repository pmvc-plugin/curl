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
        CURLOPT_FAILONERROR=>'FAILONERROR',
        CURLOPT_POST=>'POST',
        CURLOPT_POSTFIELDS=>'POSTFIELDS'
    ];
    
    function __invoke()
    {
        return $this;
    }

    function all(array $opts)
    {
        $return = [];
        foreach($opts as $k=>$v){
            $return[$this->one($k, new \PMVC\Object($v))] = $v;
        }
        return $return;
    }

    function postfields($v)
    {
        return \PMVC\plug('underscore')
            ->query()
            ->parse_str($v);
    }

    function one($k, $v=null)
    {
        $key = \PMVC\value($this->_keys, [$k]);
        if ($key) {
            if (method_exists($this, $key)) {
                if (!is_null($v)) {
                    $v =& $v();
                    $v = $this->$key($v);
                }
            }
            return $key;
        } else {
            return $k;
        }
    }
}
