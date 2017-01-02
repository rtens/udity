<?php
namespace rtens\udity\check\projection;

class FakeProjection {
    /**
     * @var object
     */
    private $projection;

    /**
     * @param object $projection
     */
    public function __construct($projection) {
        $this->projection = $projection;
    }

    function __call($name, $arguments) {
        return new ProjectedPropertyAssertion(call_user_func_array([$this->projection, $name], $arguments));
    }
}