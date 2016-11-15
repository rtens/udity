<?php
namespace rtens\udity\app\ui\fields;

use rtens\domin\delivery\web\Element;
use rtens\domin\delivery\web\WebField;
use rtens\domin\Parameter;
use rtens\udity\AggregateIdentifier;
use watoki\reflect\type\ClassType;

/**
 * Simple input for an AggregateIdentifier's key. Can be disabled.
 */
class IdentifierField implements WebField {
    /**
     * @var string
     */
    protected $class;
    /**
     * @var Parameter[]
     */
    private $disabled = [];

    /**
     * @param string $identifierClass
     */
    public function __construct($identifierClass) {
        $this->class = $identifierClass;
    }

    /**
     * @param Parameter $parameter
     * @return bool
     */
    public function handles(Parameter $parameter) {
        $type = $parameter->getType();
        return $type instanceof ClassType && $type->getClass() == $this->class;
    }

    /**
     * @param Parameter $parameter
     * @param mixed $serialized
     * @return mixed
     */
    public function inflate(Parameter $parameter, $serialized) {
        $identifierClass = $this->class;
        $identifier = new $identifierClass($serialized['key']);

        if (array_key_exists('fix', $serialized)) {
            $this->disabled[(string)$parameter] = $identifier;
        }
        return $identifier;
    }

    /**
     * @param Parameter $parameter
     * @param null|AggregateIdentifier $value
     * @return string
     */
    public function render(Parameter $parameter, $value) {
        $attributes = [
            'class' => 'form-control',
            'type' => 'text',
            'name' => $parameter->getName() . '[key]',
            'value' => $value ? $value->getKey() : ''
        ];

        if ($parameter->isRequired()) {
            $attributes['required'] = 'required';
        }

        if ($this->isDisabled($parameter, $value)) {
            $attributes['disabled'] = 'disabled';
        }

        return (string)new Element('input', $attributes);
    }

    /**
     * @param Parameter $parameter
     * @return array|Element[]
     */
    public function headElements(Parameter $parameter) {
        return [];
    }

    protected function isDisabled(Parameter $parameter, $value) {
        $param = (string)$parameter;
        return array_key_exists($param, $this->disabled) && $this->disabled[$param] == $value;
    }
}