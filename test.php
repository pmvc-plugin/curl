<?php
namespace PMVC\PlugIn\curl;
use PMVC;
use PHPUnit_Framework_TestCase;
PMVC\Load::plug();
PMVC\addPlugInFolders(['../']);

class CurlTest extends PHPUnit_Framework_TestCase
{
    private $_plug='curl';

    function testGet()
    {
        $body = [];
        $phpunit = $this;
        PMVC\plug('curl')->get('http://tw.yahoo.com',function($r) use (&$body, $phpunit){
            $body['yahoo'] = $r->body;
            $phpunit->assertTrue(empty($r->errno));
        });
        PMVC\plug('curl')->get('http://google.com',function($r) use (&$body, $phpunit){
            $body['google'] = $r->body;
            $phpunit->assertTrue(empty($r->errno));
        });
        PMVC\plug('curl')->process();
        foreach ($body as $k=>$b) {
            $this->assertContains($k, $b); 
        }

    }

    function testSigleCurl()
    {
        $curl = new CurlHelper();
        $curl->setOptions('http://tw.yahoo.com', function($r){
            $this->assertContains('yahoo', $r->body); 
            $this->assertTrue(empty($r->errno));
        });
        $curl->process();
    }

    function testFollowLocationWithPut()
    {
        $curl = PMVC\plug('curl')->put('http://tw.yahoo.com');
        $curl -> setManualFollow();
        $options = $curl->set();
        $curl->clean();
        $this->assertFalse($options[CURLOPT_FOLLOWLOCATION]);
        $this->assertTrue($curl->manualFollow);
    }

    function testFollowLocationWithPost()
    {
        $curl = PMVC\plug('curl')->post('http://tw.yahoo.com');
        $curl -> setManualFollow();
        $options = $curl->set();
        $curl->clean();
        $this->assertFalse($options[CURLOPT_FOLLOWLOCATION]);
        $this->assertTrue($curl->manualFollow);
    }

    function testGetCurlInfoKey()
    {
        $name = array(
            CURLINFO_EFFECTIVE_URL=>CurlInfo::getKey(CURLINFO_EFFECTIVE_URL),
        );
        $this->assertEquals($name[CURLINFO_EFFECTIVE_URL],'EFFECTIVE_URL');
    }

    function testParseMultiHeader()
    {
        $arr = array(
            'a: 111',
            'b: 222',
            'c: 333'
        );
        $expectedArr = array(
            'a'=>'111',
            'b'=>'222',
            'c'=>'333',
        );
        $str = join("\r\n",$arr);
        $curl = new CurlHelper(); 
        $curl->setOptions('');
        $curl->getInstance();
        $curlR = new CurlResponder('', $curl);
        $headers = $curlR->getHeaders($str);
        $this->assertEquals($expectedArr, $headers);
    }

    function testUploadFile()
    {
        $file = new \CURLFile(__DIR__.'/test/resources/upload_testfile.txt');
        $curl = PMVC\plug($this->_plug);
        $testServer = 'https://file.io';
        $curl->post($testServer, function($r){
            $body = \PMVC\fromJson($r->body); 
            $this->assertEquals('https://file.io/'.$body->key, $body->link);
            $this->assertEquals('14 days', $body->expiry);
            $this->assertTrue($body->success);
        }, array('file'=>$file), true);
        $curl->process();
    }

    function testProcessWithEmpty()
    {
        $curl = PMVC\plug($this->_plug);
        $o = $curl->get('http://tw.yahoo.com');
        $o->clean();
        $this->assertTrue(empty($o->getInstance()));
        $curl->process();
    }
}
