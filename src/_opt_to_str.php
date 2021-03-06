<?php
namespace PMVC\PlugIn\curl;

use PMVC\PlugIn\url\Query;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\OPT_TO_STR';

\PMVC\initPlugin(['url'=>null], true);

/**
 * @see https://gist.github.com/blainesch/4626922
 */
class OPT_TO_STR
{
    private $_keys = [
        CURLOPT_SHARE=>'SHARE',
        CURLINFO_HEADER_OUT=>'HEADER_OUT',
        CURLOPT_COOKIE=>'COOKIE',
        CURLOPT_CONNECTTIMEOUT=>'CONNECTTIMEOUT',
        CURLOPT_FOLLOWLOCATION=>'FOLLOWLOCATION',
        CURLOPT_FAILONERROR=>'FAILONERROR',
        CURLOPT_FORBID_REUSE=>'FORBID_REUSE',
        CURLOPT_FRESH_CONNECT=>'FRESH_CONNECT',
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
        CURLOPT_NOSIGNAL=>'NOSIGNAL',
        CURLOPT_DNS_CACHE_TIMEOUT=>'DNS_CACHE_TIMEOUT',
        CURLOPT_TCP_NODELAY=>'TCP_NODELAY',
    ];
    
    function __invoke()
    {
        return $this;
    }

    function all(array $opts)
    {
        $return = [];
        if (isset($opts[CURLOPT_SHARE])) {
            $opts[CURLOPT_SHARE] = print_r(
                $opts[CURLOPT_SHARE],
                true
            );
        }
        foreach($opts as $k=>$v){
            $return[$this->_one($k, $v)] = $v;
        }
        return $return;
    }

    function postfields($v)
    {
        return Query::parse_str($v);
    }

    private function _one($k, &$v)
    {
        $key = \PMVC\value($this->_keys, [$k]);
        if ($key) {
            if (method_exists($this, $key)) {
                if (!is_null($v)) {
                    $raw = $v;
                    $v = [
                        'cook' => $this->$key($v),
                        'raw' => $raw 
                    ];
                }
            }
            return $key;
        } else {
            return $k;
        }
    }
}
