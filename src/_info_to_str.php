<?php
namespace PMVC\PlugIn\curl;

${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\info_to_str';

class info_to_str
{
    private $_keys = [
        CURLINFO_EFFECTIVE_URL=>'url',
        CURLINFO_CONTENT_TYPE=>'content_type',
        CURLINFO_HTTP_CODE=>'http_code',
        CURLINFO_HEADER_SIZE=>'header_size',
        CURLINFO_REQUEST_SIZE=>'request_size',
        CURLINFO_FILETIME=>'filetime',
        CURLINFO_SSL_VERIFYRESULT=>'ssl_verify_result',
        CURLINFO_REDIRECT_COUNT=>'redirect_count',
        CURLINFO_TOTAL_TIME=>'total_time',
        CURLINFO_NAMELOOKUP_TIME=>'namelookup_time',
        CURLINFO_CONNECT_TIME=>'connect_time',
        CURLINFO_PRETRANSFER_TIME=>'pretransfer_time',
        CURLINFO_SIZE_UPLOAD=>'size_upload',
        CURLINFO_SIZE_DOWNLOAD=>'size_download',
        CURLINFO_SPEED_DOWNLOAD=>'speed_download',
        CURLINFO_SPEED_UPLOAD=>'speed_upload',
        CURLINFO_CONTENT_LENGTH_DOWNLOAD=>'download_content_length',
        CURLINFO_CONTENT_LENGTH_UPLOAD=>'upload_content_length',
        CURLINFO_STARTTRANSFER_TIME=>'starttransfer_time',
        CURLINFO_REDIRECT_TIME=>'redirect_time',
        CURLINFO_REDIRECT_URL=>'redirect_url',
        CURLINFO_PRIMARY_IP=>'primary_ip',
        CURLINFO_CERTINFO=>'certinfo',
        CURLINFO_PRIMARY_PORT=>'primary_port',
        CURLINFO_LOCAL_IP=>'local_ip',
        CURLINFO_LOCAL_PORT=>'local_port',
        CURLINFO_HEADER_OUT=>'header_out',
        CURLINFO_PRIVATE=>'private'
    ];

    function __invoke()
    {
        return $this;
    }

    function flip(array $infos)
    {
        $return = [];
        $flip = array_flip($this->_keys);
        foreach($infos as $k=>$v){
            $return[$this->one($k, $flip)] = $v;
        }
        return $return;
    }

    function one($k, $to = null)
    {
        if (is_null($to)) {
            $to = $this->_keys;
        }
        if (isset($to[$k])) {
            return $to[$k];
        } else {
            return $k;
        }
    }
}
