<?php
namespace PMVC\PlugIn\curl;
use PMVC;
use PHPUnit_Framework_TestCase;
use CURLFILE;

class CurlTest extends PHPUnit_Framework_TestCase
{
    private $_plug='curl';

    function setup()
    {
        \PMVC\initPlugin(['curl'=>null],true);
    }

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
        $plug = \PMVC\plug($this->_plug);
        $name = array(
            CURLINFO_EFFECTIVE_URL=>$plug->info_to_str()->one(CURLINFO_EFFECTIVE_URL),
        );
        $this->assertEquals($name[CURLINFO_EFFECTIVE_URL],'url');
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
        $filePath = __DIR__.'/resources/upload_testfile.txt';
        $file = new CURLFile($filePath);
        $curl = PMVC\plug($this->_plug);
        $testServer = 'https://file.io';
        $curl->post(
            $testServer,
            function($r){
                if (200!==$r->code) {
                    return !trigger_error(print_r($r,true));
                }
                $body = \PMVC\fromJson($r->body); 
                $this->assertEquals('https://file.io/'.$body->key, $body->link);
                $this->assertEquals('14 days', $body->expiry);
                $this->assertTrue($body->success);
            },
            ['file'=>$file],
            true
        )->set([
            CURLOPT_CONNECTTIMEOUT=>1,
            CURLOPT_TIMEOUT=>3
        ]);
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

    function testSetHeader()
    {
        $curl = PMVC\plug($this->_plug);
        $o = $curl->post(
            'http://tw.yahoo.com',
            null,
            [],
            null,
            true
        );
        $actual = $o->set([
            CURLOPT_HTTPHEADER=> [
                'X-fake: fake'
            ]
        ]);
        $expects = [
            'Content-Type: application/json',
            'Content-Length: 2',
            'X-fake: fake'
        ];
        $this->assertEquals($expects, $actual[CURLOPT_HTTPHEADER]);
    }

    function testShouldOnlyGetOnce()
    {
        $curl = PMVC\plug($this->_plug);
        $i = 0;
        $callback = function() use(&$i){
            $i++;
        };
        $curl->get('https://google.com', $callback);
        $tw = $curl->get('https://google.com.tw', $callback);
        $tw->clean();
        $curl->process();
        $this->assertEquals(1, $i);
    }
}
