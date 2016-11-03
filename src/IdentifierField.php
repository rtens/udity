<?php
namespace rtens\proto;

use rtens\domin\delivery\web\Element;
use rtens\domin\delivery\web\WebField;
use rtens\domin\Parameter;
use watoki\reflect\type\ClassType;

class IdentifierField implements WebField {
    /**
     * @var \ReflectionClass
     */
    private $identifierClass;

    /**
     * @param \ReflectionClass $identifierClass
     */
    public function __construct(\ReflectionClass $identifierClass) {
        $this->identifierClass = $identifierClass;
    }

    /**
     * @param Parameter $parameter
     * @return bool
     */
    public function handles(Parameter $parameter) {
        $type = $parameter->getType();
        return $type instanceof ClassType && $type->getClass() == $this->identifierClass->getName();
    }

    /**
     * @param Parameter $parameter
     * @param string $serialized
     * @return mixed
     */
    public function inflate(Parameter $parameter, $serialized) {
        return $this->identifierClass->newInstance($serialized);
    }

    /**
     * @param Parameter $parameter
     * @param AggregateIdentifier $value
     * @return string
     */
    public function render(Parameter $parameter, $value) {
        return (string)new Element('input', array_merge([
            'class' => 'form-control',
            'type' => 'text',
            'name' => $parameter->getName(),
            'value' => $value ? $value->getKey() : ''
        ], $parameter->isRequired() ? [
            'required' => 'required'
        ] : []));    }

    /**
     * @param Parameter $parameter
     * @return array|Element[]
     */
    public function headElements(Parameter $parameter) {
        return [];
    }

}