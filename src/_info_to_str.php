<?php
namespace PMVC\PlugIn\curl;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\info_to_str';

class info_to_str
{
    private $_keys = [
        CURLINFO_EFFECTIVE_URL=>'EFFECTIVE_URL',
        CURLINFO_HTTP_CODE=>'HTTP_CODE',
        CURLINFO_FILETIME=>'FILETIME',
        CURLINFO_TOTAL_TIME=>'TOTAL_TIME',
        CURLINFO_NAMELOOKUP_TIME=>'NAMELOOKUP_TIME',
        CURLINFO_CONNECT_TIME=>'CONNECT_TIME',
        CURLINFO_PRETRANSFER_TIME=>'PRETRANSFER_TIME',
        CURLINFO_STARTTRANSFER_TIME=>'STARTTRANSFER_TIME',
        CURLINFO_REDIRECT_COUNT=>'REDIRECT_COUNT',
        CURLINFO_REDIRECT_TIME=>'REDIRECT_TIME',
        CURLINFO_SIZE_UPLOAD=>'SIZE_UPLOAD',
        CURLINFO_SIZE_DOWNLOAD=>'SIZE_DOWNLOAD',
        CURLINFO_SPEED_DOWNLOAD=>'SPEED_DOWNLOAD',
        CURLINFO_SPEED_UPLOAD=>'SPEED_UPLOAD',
        CURLINFO_HEADER_SIZE=>'HEADER_SIZE',
        CURLINFO_HEADER_OUT=>'HEADER_OUT',
        CURLINFO_REQUEST_SIZE=>'REQUEST_SIZE',
        CURLINFO_SSL_VERIFYRESULT=>'SSL_VERIFYRESULT',
        CURLINFO_CONTENT_LENGTH_DOWNLOAD=>'CONTENT_LENGTH_DOWNLOAD',
        CURLINFO_CONTENT_LENGTH_UPLOAD=>'CONTENT_LENGTH_UPLOAD',
        CURLINFO_CONTENT_TYPE=>'CONTENT_TYPE',
        CURLINFO_PRIVATE=>'PRIVATE'
    ];

    function __invoke()
    {
        return $this;
    }

    function all(array $infos)
    {
        $return = [];
        foreach($infos as $k=>$v){
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
