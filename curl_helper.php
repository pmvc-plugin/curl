<?php

interface curl
{
    public function set_options($url, $cus_options=array());
    public function run($more=array());
    public function set_as_post($url, $post_field=array(), $options=array());
} 

/**
* a curl wraper for ci
*/
class Curl_Helper implements curl
{

    /**
     * @var default curl options
     */
    private $opts = array(
         CURLOPT_HEADER         => TRUE
        ,CURLOPT_VERBOSE        => FALSE
        ,CURLOPT_RETURNTRANSFER => TRUE
        ,CURLOPT_FOLLOWLOCATION => TRUE
    );

    /**
     * @var curl resource
     */
    private $oCurl=null;

    /**
    * set curl option
    * @param string $url
    * @param array $cus_options curl option
    * @see http://php.net/manual/en/function.curl-setopt.php
    * @return curl_setopt_array result
    */
    public function set_options($url, $cus_options=array()){
        //init options
        $opts = $this->opts;
        //assing custom options
        if(is_array($cus_options)){
            foreach($cus_options as $k=>$v)
            {
                if (CURLOPT_TIMEOUT_MS == $k || CURLOPT_CONNECTTIMEOUT_MS == $k)//work around for curl options CURLOPT_TIMEOUT_MS and CURLOPT_CONNECTTIMEOUT_MS doesn't work.
                {
                    $v = ($v < 1000) ? 1 : $v /1000;
                    if(CURLOPT_TIMEOUT_MS == $k)
                        $k = CURLOPT_TIMEOUT;
                    else
                        $k = CURLOPT_CONNECTTIMEOUT;
                } 
                $opts[$k] = $v;
            }
        } 
        $opts[CURLOPT_URL]=$url;
        if(is_null($this->oCurl)){
            $this->oCurl = curl_init();
        }
        return curl_setopt_array($this->oCurl,$opts);
    }

    /**
     * return curl execute result
     * @see http://php.net/manual/en/function.curl-getinfo.php
     * @return CurlResponder
     */
    public function run($more=array()){
        $oCurl = $this->oCurl;
        $return = curl_exec($oCurl);
        $r = new Curl_Responder($return,$oCurl,$more);
        $this->clean();
        return $r;
    } 

    /**
     * set as post
    * @param string $url
    * @param array $post_field
    * @param array $cus_options curl option
    * @return curl_setopt_array result
     */
    public function set_as_post($url,$post_field=array(),$cus_options=array()){
        $cus_options[CURLOPT_POST] = true;
        $cus_options[CURLOPT_POSTFIELDS] = $post_field;
        return $this->set_options($url, $cus_options);
    }


    /**
     * @return curl resource 
     */
    public function get_instance(){
        return $this->oCurl;
    }

    /**
     * reset  curl resource
     */
    public function clean(){
        if(is_resource($this->oCurl)){
            curl_close($this->oCurl);
        }
        $this->oCurl=null;
    }

}


/**
 * implement for multi curl
 */
class Multi_Curl_Helper
{

    /**
     * @var Curl_Helper array
     */
    private $curl_map;

    /**
     * @param array $curl_map a hashmap for Curl_Helper, need assign key, the return result will reference
     */
    public function __construct($curl_map=null){
        if($curl_map){
            $this->add($curl_map);
        }
    }

    /**
     * add Curl_Helper to Multi_Curl_Helper 
     * @param $ocurl
     * @param $key a hashmap for Curl_Helper, need assign key, the return result will reference
     */
    public function add($ocurl,$key='',$function=null)
    {
        $data = (object)array(
            'obj'=>$ocurl,
            'func'=>$function
        );
        if(!strlen($key)){
            $this->curl_map[]=$data;
        }else{
            $this->curl_map[$key]=$data;
        }
    }

    /**
     * execute multi curl
     * @return hasmap by CurlResponder
     */
    public function run($more=array()){
        $mh = curl_multi_init();
        if(empty($this->curl_map) || !is_array($this->curl_map)){
            return false;
        }
        foreach($this->curl_map as $hash=>$data){
            $oCurl = $data->obj->get_instance();
            curl_multi_add_handle($mh,$oCurl);
        }
        // set run flag
        $running = null;
        do {
            curl_multi_exec($mh,$running);
        } while ($running > 0);
        $result = array();
        foreach($this->curl_map as $hash=>$data){
            $oCurl = $data->obj->get_instance();
            $return = curl_multi_getcontent($oCurl); 
            $r = new Curl_Responder($return,$oCurl,$more);
            if(is_callable($data->func)){
                call_user_func($data->func,$r);
            }
            $result[$hash]=$r;
            curl_multi_remove_handle($mh,$oCurl);
        }
        curl_multi_close($mh);
        return $result;
    }
}


/**
 * keep curl respone data
 */
class Curl_Responder
{
    /**
     * @var http respone code
     */
    var $code;

    /**
     * @var http respone header 
     */
    var $header;

    /**
     * @var http respone header_raw 
     */
    var $header_raw;

    /**
     * @var http respone header 
     */
    var $body;

    /**
     * @var http respone more information 
     */
    var $more;

    /**
     * @var curl error code
     */
    var $errno;

    /**
     * construct
     */
    public function __construct($return,$oCurl,$more=array())
    {
        $header_size = curl_getinfo($oCurl,CURLINFO_HEADER_SIZE);
        $this->header_raw = substr($return, 0, $header_size);
        $this->header = $this->getHeaders($this->header_raw); 
        if ( !empty($this->header['content-encoding']) &&
             'gzip' === $this->header['content-encoding']
           ) {
            $this->body = gzinflate(substr($return, $header_size+10,-8));
        } else {
            $this->body = substr($return, $header_size);
        }
        $this->code = curl_getinfo($oCurl,CURLINFO_HTTP_CODE);
        $this->errno = curl_errno($oCurl);
        if(!empty($more)){
            foreach ($more as $key){
                $this->more[$key] = curl_getinfo($oCurl,$key);
            }
        }
    }

    /**
     * parse header String
     */
     public function getHeaders($str)
     {
        $headers = str_replace("\r","",$str);
        $headers = explode("\n",$headers);
        foreach($headers as $value){
            $header = explode(": ",$value);
            if(!empty($header[0]) && empty($header[1])){
                $headerdata['status'] = $header[0];
            }   
            elseif(!empty($header[0]) && !empty($header[1])){
                $headerdata[strtolower($header[0])] = $header[1];
            }   
        }   
        return $headerdata;
     }
}

?>
