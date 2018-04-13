<?php

namespace PMVC\PlugIn\curl;

use PHPUnit_Framework_TestCase;

\PMVC\initPlugIn(['curl'=>null]);

class CurlResponderPurgeTest
    extends PHPUnit_Framework_TestCase
{
    private $_plug='curl';

    function testEmptyPurge()
    {
        $respond = new CurlResponder('', null);
        $a = $respond->purge();
        $this->assertNull($a);
    }

    function testCallbackPurge()
    {
        $respond = new CurlResponder('', null);
        $respond->purge = function() {
            return 'foo';
        };
        $a = $respond->purge();
        $this->assertEquals('foo', $a);
    }

    function testCallPurgeAttribute()
    {
        $respond = new CurlResponder('', null);
        $respond->purge = function() {
            return 'foo';
        };
        $a = call_user_func($respond->purge);
        $this->assertEquals('foo', $a);
    }

    function testCallPurgeMethod()
    {
        $respond = new CurlResponder('', null);
        $i = 0;
        $respond->purge = function() use(&$i) {
            $i++;
            return $i.'-'.get_called_class();
        };
        $a = call_user_func($respond->purge);
        $this->assertEquals(
            '1-PMVC\PlugIn\curl\CurlResponderPurgeTest', 
            $a
        );
        $b = call_user_func([$respond,'purge']);
        $this->assertEquals(
            '2-PMVC\PlugIn\curl\CurlResponderPurgeTest', 
            $b
        );
    }
}
