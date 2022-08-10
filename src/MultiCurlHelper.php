<?php
namespace PMVC\PlugIn\curl;

use SplObjectStorage;

/**
 * implement for multi curl
 */
class MultiCurlHelper
{
    /**
     * @var array CurlHelper
     */
    private $_curls;

    /**
     * Should not greater than 10 to avoid over threads.
     * Else will make db (ssdb) crash.
     *
     * @var int payload['max']
     * @var array payload
     */
    private $_payload;

    public function __construct($payload = null)
    {
        $this->_curls = new SplObjectStorage();
        $this->_payload = $payload;
    }

    /**
     * add CurlHelper to Multi_Curl_Helper
     *
     * @param $ocurl
     * @param $key a hashmap for CurlHelper, need assign key, the return result will reference
     */
    public function add($ocurl)
    {
        $this->_curls->attach($ocurl);
    }

    /**
     * Get Curl map
     */
    public function getCurls()
    {
        return $this->_curls;
    }

    /**
     * Get Curl map
     */
    public function clean()
    {
        $this->_curls->removeAll($this->_curls);
    }

    /**
     * Execute multi curl
     *
     * @return bool
     */
    public function process($more = [])
    {
        if (1 >= count($this->_curls)) {
            $this->_curls->rewind();
            $obj = $this->_curls->current();
            $this->clean();
            if ($obj) {
                $oCurl = $obj->getInstance();
                if ($oCurl) {
                    $obj->process($more);
                }
            }
            return true;
        }
        $curlPool = clone $this->_curls;
        $this->clean();
        $curlPool->rewind();
        $executePool = new SplObjectStorage();
        $i = 0;
        $max =
            !empty($this->_payload['max']) && is_int($this->_payload['max'])
                ? $this->_payload['max']
                : 10;
        while ($curlPool->valid()) {
            $obj = $curlPool->current();
            $curlPool->next();
            $oCurl = $obj->getInstance();
            if (!empty($oCurl)) {
                $executePool->attach($obj);
            }
            $i++;
            if ($i >= $max) {
                $this->_process($more, $executePool);
                $i = 0;
                $curlPool->removeAll($executePool);
                $executePool->removeAll($executePool);
            }
        }
        if (count($executePool)) {
            $this->_process($more, $executePool);
        }
        $curlPool->removeAll($curlPool);
        $executePool->removeAll($executePool);
    }

    /**
     * Execute multi curl
     *
     * @return bool
     */
    private function _process($more, $executePool)
    {
        $executePool->rewind();

        $multiCurl = curl_multi_init();
        while ($executePool->valid()) {
            $obj = $executePool->current();
            $executePool->next();
            $oCurl = $obj->getInstance();
            curl_multi_add_handle($multiCurl, $oCurl);
        }
        // set run flag
        $running = null;
        do {
            $multiExec = curl_multi_exec($multiCurl, $running);
        } while ($multiExec === CURLM_CALL_MULTI_PERFORM);

        while ($running && $multiExec === CURLM_OK) {
            if (curl_multi_select($multiCurl) == -1) {
                usleep(30000);
            }
            do {
                $multiExec = curl_multi_exec($multiCurl, $running);
            } while ($multiExec == CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($executePool as $obj) {
            $obj->process($more, function ($oCurl) use ($multiCurl) {
                $return = curl_multi_getcontent($oCurl);
                curl_multi_remove_handle($multiCurl, $oCurl);
                return $return;
            });
        }
        curl_multi_close($multiCurl);

        return true;
    }
}
