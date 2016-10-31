<?php
namespace rtens\proto;

use rtens\domin\reflection\types\TypeFactory;
use watoki\reflect\type\StringType;
use watoki\reflect\type\UnknownType;

class DefaultTypeFactory extends TypeFactory {

    public function fromString($hintString, \ReflectionClass $class) {
        return $this->defaultToString(parent::fromString($hintString, $class));
    }

    public function fromTypeHints(array $hints, \ReflectionClass $class) {
        return $this->defaultToString(parent::fromTypeHints($hints, $class));
    }

    private function defaultToString($type) {
        if ($type instanceof UnknownType) {
            return new StringType();
        }
        return $type;
    }

}