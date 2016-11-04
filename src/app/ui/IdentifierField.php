<?php
namespace rtens\proto\app\ui;

use rtens\domin\delivery\web\Element;
use rtens\domin\delivery\web\WebField;
use rtens\domin\Parameter;

/**
 * Simple input for an AggregateIdentifier's key. Can be disabled.
 */
class IdentifierField implements WebField {

    /**
     * @param Parameter $parameter
     * @return bool
     */
    public function handles(Parameter $parameter) {
        return false;
    }

    /**
     * @param Parameter $parameter
     * @param string $serialized
     * @return mixed
     */
    public function inflate(Parameter $parameter, $serialized) {
        return null;
    }

    /**
     * @param Parameter $parameter
     * @param mixed $value
     * @return string
     */
    public function render(Parameter $parameter, $value) {
        return '';
    }

    /**
     * @param Parameter $parameter
     * @return array|Element[]
     */
    public function headElements(Parameter $parameter) {
        return [];
    }
}