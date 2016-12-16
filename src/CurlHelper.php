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
        $options[CURLOPT_URL]=$url;
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
            foreach ($options as $k=>$v) {
                $this->_opts[$k] = $v;
            }
        }
        return $this->_opts;
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
            ,CURLOPT_FAILONERROR=>true
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
            return [
                'option' => \PMVC\plug('curl')
                    ->opt_to_str()
                    ->all($this->_opts),
                'respond'=>$r,
                'body'=>\PMVC\fromJson($r->body)
            ];
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
            $this->_opts[CURLOPT_URL] = 
                (string)$this->_opts[CURLOPT_URL];
            curl_setopt_array($this->_oCurl, $this->_opts);
            \PMVC\dev(function(){
                return \PMVC\plug('curl')
                    ->opt_to_str()
                    ->all($this->_opts);
            },'req');
            $this->setManualFollow();
        }
        return $this->_oCurl;
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