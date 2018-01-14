<?php
namespace PMVC\PlugIn\curl;

use PMVC\PlugIn\url\Query;
use PMVC\BaseObject;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\OPT_TO_STR';

\PMVC\initPlugin(['url'=>null], true);

/**
 * @see https://gist.github.com/blainesch/4626922
 */
class OPT_TO_STR
{
    private $_keys = [
        CURLOPT_COOKIE=>'COOKIE',
        CURLOPT_CONNECTTIMEOUT=>'CONNECTTIMEOUT',
        CURLOPT_FOLLOWLOCATION=>'FOLLOWLOCATION',
        CURLOPT_FAILONERROR=>'FAILONERROR',
        CURLOPT_FORBID_REUSE=>'FORBID_REUSE',
        CURLOPT_HTTPHEADER=>'HTTPHEADER',
        CURLOPT_HEADER=>'Respond Contain header',
        CURLOPT_NOBODY=>'NOBODY',
        CURLOPT_POST=>'POST',
        CURLOPT_POSTFIELDS=>'POSTFIELDS',
        CURLOPT_RETURNTRANSFER=>'RETURNTRANSFER',
        CURLOPT_REFERER=>'REFERER',
        CURLOPT_SSL_VERIFYPEER=>'SSL_VERIFYPEER',
        CURLOPT_SSL_VERIFYHOST=>'SSL_VERIFYHOST',
        CURLOPT_TIMEOUT=>'TIMEOUT',
        CURLOPT_URL=>'URL',
        CURLOPT_USERAGENT=>'USERAGENT',
        CURLOPT_VERBOSE=>'VERBOSE',
    ];
    
    function __invoke()
    {
        return $this;
    }

    function all(array $opts)
    {
        $return = [];
        foreach($opts as $k=>$v){
            $return[$this->one($k, new BaseObject($v))] = $v;
        }
        return $return;
    }

    function postfields($v)
    {
        return Query::parse_str($v);
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
