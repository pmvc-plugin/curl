<?php
PMVC\Load::plug();
PMVC\setPlugInFolder('../');
class CurlTest extends PHPUnit_Framework_TestCase
{
    function testGet()
    {
        $phpunit = $this;
        PMVC\plug('curl')->get('http://tw.yahoo.com',function($r) use ($phpunit){
            $phpunit->assertTrue(empty($r->errno));
        });
        PMVC\plug('curl')->run();
    }
}
