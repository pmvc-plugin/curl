<?php

namespace PMVC\PlugIn\curl;

use PMVC\TestCase;

class CleanJsonPTest extends TestCase
{
    private $_plug='curl';

    public function testCleanJsonP()
    {
        $s1 = 'foo({"aaa": "bbb"});';
        $curl = \PMVC\plug($this->_plug); 
        $this->assertEquals('{"aaa": "bbb"}', $curl->clean_json_p($s1));
    }
}
