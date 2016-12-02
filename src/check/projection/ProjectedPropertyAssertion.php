<?php
namespace rtens\udity\check\projection;

use rtens\udity\check\FailedAssertion;

class ProjectedPropertyAssertion {
    /**
     * @var mixed
     */
    private $value;

    /**
     * @param mixed $value
     */
    public function __construct($value) {
        $this->value = $value;
    }

    public function shouldEqual($expected) {
        if ($this->value != $expected) {
            throw new FailedAssertion("Expected [$expected] but got [{$this->value}]");
        }
    }
}