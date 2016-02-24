<?php
namespace PMVC\PlugIn\curl;
use PMVC;
use PHPUnit_Framework_TestCase;
PMVC\Load::plug();
PMVC\setPlugInFolder('../');
class CurlTest extends PHPUnit_Framework_TestCase
{
    private $_plug='curl';

    function testGet()
    {
        $phpunit = $this;
        PMVC\plug('curl')->get('http://tw.yahoo.com',function($r) use ($phpunit){
            $phpunit->assertContains('yahoo', $r->body); 
            $phpunit->assertTrue(empty($r->errno));
        });
        PMVC\plug('curl')->process();
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
        return;
        $file = new \CURLFile(__DIR__.'/test/resources/upload_testfile.txt');
        $curl = PMVC\plug('curl');
        $testServer = 'http://posttestserver.com/post.php?dir=example';
        $curl->post($testServer, function($r){
            var_dump($r);
        }, array('submitted'=>$file), true);
        $curl->process();
    }
}
