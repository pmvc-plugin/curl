<?php
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
}
