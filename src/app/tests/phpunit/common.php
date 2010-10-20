<?php
define('DISABLE_AUTO_DISPATCH', 1);

$cake = realpath(dirname(__FILE__).'/../../../cake/console/cake.php');
require_once($cake);
class TestDisptacher extends ShellDispatcher {
	function TestDisptacher($args) {
		set_time_limit(0);
                $this->__initConstants();
		$this->parseParams($args);
		$this->_initEnvironment();
	}
}
new TestDisptacher(array($cake));

class SSTest extends PHPUnit_Framework_TestCase {

}
