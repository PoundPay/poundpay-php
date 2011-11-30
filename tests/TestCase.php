<?php
namespace Services\PoundPay;
require_once 'Services/PoundPay/Autoload.php';
require_once 'PHPUnit/Autoload.php';

class TestCase extends \PHPUnit_Framework_TestCase {

    public function assertAny($constraint, $other) {
        foreach ($other as $element) {
            if ($constraint->evaluate($element, '', TRUE)) {
                return;
            }
        }
        $this->fail(sprintf('Failed asserting any in %s %s', print_r($other, true), $constraint->toString()));
    }

    protected function makeTestData($id = 'test', $count = 3) {
        $data = array();
        for ($i = 1; $i <= $count; ++$i) {
            $data["{$id}_key$i"] = "{$id}_val$i";
        }
        return $data;
    }
}
