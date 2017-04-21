<?php

namespace PMVC\PlugIn\curl;

use PHPUnit_Framework_TestCase;

class CurlHelperTest extends PHPUnit_Framework_TestCase
{
    private $_plug='curl';

    function testGetOption()
    {
        $url = \PMVC\plug('url')->getUrl('http://xxx.xxx?a=1');
        $curl = \PMVC\plug($this->_plug); 
        $url->query->foo = 'bar1';
        $o1 = $curl->get($url);
        $url->query->foo = 'bar2';
        $o2 = $curl->get($url);
        $hash1 = \PMVC\hash($o1->set());  
        $hash2 = \PMVC\hash($o2->set());  
        $this->assertNotEquals($hash1, $hash2);
    }
}
