<?php
namespace PMVC\PlugIn\curl;

/**
* Curl helper library 
*/
class CurlHelper implements CurlInterface
{
    /**
     * @var custom follow location
     */
    public $manualFollow = false;

    /**
     * @var function 
     */
    private $_function;

    /**
     * @var default curl options
     */
    private $_opts = [];

    /**
     * @var curl resource
     */
    private $_oCurl = null;

    /**
    * Set curl option
    *
    * @param  string $url
    * @param  array  $options curl option
    * @see    http://php.net/manual/en/function.curl-setopt.php
    * @see    https://github.com/bagder/curl/blob/master/docs/libcurl/symbols-in-versions
    * @return array result
    */
    public function setOptions(
        $url, 
        callable $function=null, 
        array $options=[]
    ) {
        $this->_opts = $this->getDefaultOptions();
        // keep consistency when somewhere get url 
        \PMVC\dev(function() use (&$options){
            $options[CURLINFO_HEADER_OUT] = 1;
        }, 'req');
        $options[CURLOPT_URL]=(string)$url;
        $this->_function = $function;
        return $this->set($options);
    }

    /**
     * @param array $options curl option
     */
    public function set($options = null)
    {
        //assing custom options
        if (is_array($options)) {
            if (isset($options[CURLOPT_HTTPHEADER])) {
                $this->_opts[CURLOPT_HTTPHEADER] = 
                    array_merge(
                        \PMVC\get($this->_opts, CURLOPT_HTTPHEADER, []),
                        $options[CURLOPT_HTTPHEADER]
                    );
                unset($options[CURLOPT_HTTPHEADER]);
            }
            foreach ($options as $k=>$v) {
                $this->_opts[$k] = $v;
            }
        }
        return $this->_opts;
    }

    /**
     * Get hash
     */
     public function getHash()
     {
        $options = $this->set();
        unset($options[CURLINFO_HEADER_OUT]);
        return \PMVC\hash($options);
     }

    /**
     * Get default options
     */
    public function getDefaultOptions()
    {
        return [ 
             CURLOPT_HEADER         => true
            ,CURLOPT_VERBOSE        => false
            ,CURLOPT_RETURNTRANSFER => true
            ,CURLOPT_FOLLOWLOCATION => true
            ,CURLOPT_SSL_VERIFYHOST => false
            ,CURLOPT_SSL_VERIFYPEER => false
            // For get error body
            ,CURLOPT_FAILONERROR    => false
        ];
    }

    /**
     * Set manual follow location
     */
    public function setManualFollow()
    {
        $isNotGet = ( 
            ( !empty($this->_opts[CURLOPT_CUSTOMREQUEST]) && 
              'GET' !== $this->_opts[CURLOPT_CUSTOMREQUEST] 
            ) ||
            !empty($this->_opts[CURLOPT_POST])
        );
        if ( $isNotGet &&
            !empty($this->_opts[CURLOPT_FOLLOWLOCATION])
        ) {
            $this->set([CURLOPT_FOLLOWLOCATION=>false]);
            $this->manualFollow = true;
        }
    }

    /**
     * Return curl execute result
     *
     * @return CurlResponder
     */
    public function process(array $more=[], $func='curl_exec')
    {
        $oCurl = $this->getInstance();
        if (!$oCurl) {
            return !trigger_error('Not set any CURL option yet.');
        }
        $return = call_user_func($func,$oCurl);
        $r = new CurlResponder($return, $this, $more);
        \PMVC\dev(function() use($r) {
            return \PMVC\plug('curl')->
                curl_dev(
                    $r,
                    $this->_opts 
                );
        },'curl');
        if (is_callable($this->_function)) {
            call_user_func($this->_function, $r, $this);
        }
        $this->clean();
        return true;
    }

    /**
     * @return curl resource
     */
    public function getInstance()
    {
        if (empty($this->_opts)) {
            return false;
        }
        if (is_null($this->_oCurl)) {
            $this->_oCurl = curl_init();
            curl_setopt_array($this->_oCurl, $this->_opts);
            \PMVC\dev(
            /**
             * @help Curl request information
             */
            function(){
                return \PMVC\plug('curl')
                    ->opt_to_str()
                    ->all($this->_opts);
            }, 'req');
            $this->setManualFollow();
        }
        return $this->_oCurl;
    }

    public function setCallback($cb)
    {
        $this->_function = $cb;
        return $this;
    }

    /**
     * @return callback
     */
     public function getCallback()
     {
        return $this->_function;
     }

    /**
     * Reset  curl resource
     */
    public function clean()
    {
        if (is_resource($this->_oCurl)) {
            curl_close($this->_oCurl);
        }
        $this->_oCurl = null;
        $this->_opts = [];
    }

    public function __destruct()
    {
        $this->clean();
    }
}
