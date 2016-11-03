<?php
namespace rtens\proto;

use rtens\domin\delivery\web\Element;
use rtens\domin\delivery\web\WebField;
use rtens\domin\Parameter;
use watoki\reflect\type\ClassType;

class IdentifierSelectorField implements WebField {
    /**
     * @var \ReflectionClass
     */
    private $identifierClass;
    /**
     * @var callable
     */
    private $options;

    /**
     * @param \ReflectionClass $identifierClass
     * @param callable $options
     */
    public function __construct(\ReflectionClass $identifierClass, callable $options) {
        $this->identifierClass = $identifierClass;
        $this->options = $options;
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
        return (string)new Element('select', [
            'name' => $parameter->getName(),
            'class' => 'form-control'
        ], $this->renderOptions($value));
    }

    private function renderOptions(AggregateIdentifier $value = null) {
        $options = [];
        foreach (call_user_func($this->options) as $key => $caption) {
            $options[] = new Element('option', array_merge([
                'value' => $key
            ], !is_null($value) && $key == $value->getKey() ? [
                'selected' => 'selected'
            ] : []), [
                $caption
            ]);
        }
        return $options;
    }

    /**
     * @param Parameter $parameter
     * @return array|Element[]
     */
    public function headElements(Parameter $parameter) {
        return [];
    }
}