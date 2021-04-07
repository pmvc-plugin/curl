<?php

namespace PMVC\PlugIn\curl;

use PHPUnit_Framework_TestCase;

\PMVC\initPlugIn(['curl'=>null]);

class CurlResponderFromObjectTest 
    extends PHPUnit_Framework_TestCase
{
    function testFromObject()
    {
      $a = new \StdClass();
      $a->foo = 'xxx';
      $rObj = CurlResponder::fromObject($a);
      $this->assertEquals('xxx', $rObj->foo);
    }
}
