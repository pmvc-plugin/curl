<?php
namespace PMVC\PlugIn\curl;
use PMVC;
use PHPUnit_Framework_TestCase;
PMVC\Load::plug();
PMVC\setPlugInFolder('../');
class CurlTest extends PHPUnit_Framework_TestCase
{
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
        $curl->setOptions('http://tw.yahoo.com');
        $r = $curl->process();
        $this->assertContains('yahoo', $r->body); 
        $this->assertTrue(empty($r->errno));
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
}
