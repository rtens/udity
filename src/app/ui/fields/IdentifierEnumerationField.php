<?php
namespace rtens\udity\app\ui\fields;

use rtens\domin\delivery\web\Element;
use rtens\domin\Parameter;
use rtens\udity\AggregateIdentifier;
use rtens\udity\app\Application;
use rtens\udity\domain\query\IdentifierOptionsList;
use rtens\udity\Query;

/**
 * Drop-down selection for AggregateIdentifier. Can be disabled.
 */
class IdentifierEnumerationField extends IdentifierField {
    /**
     * @var Application
     */
    private $app;
    /**
     * @var string
     */
    private $listClass;

    /**
     * @param Application $app
     * @param string $listClass
     * @param string $class
     */
    public function __construct(Application $app, $listClass, $class) {
        parent::__construct($class);
        $this->app = $app;
        $this->listClass = $listClass;
    }

    /**
     * @param Parameter $parameter
     * @param AggregateIdentifier|null $value
     * @return string
     */
    public function render(Parameter $parameter, $value) {
        $elements = [];
        $attributes = [
            'class' => 'form-control',
            'name' => $parameter->getName() . '[key]',
        ];

        if ($this->isDisabled($parameter, $value)) {
            $elements[] = $this->hiddenElement($value, $attributes['name']);

            $attributes['disabled'] = 'disabled';
            $attributes['name'] = null;
        }

        $elements[] = new Element('select', $attributes, $this->renderOptions($value));

        return (string)new Element('div', [], $elements);
    }

    private function renderOptions(AggregateIdentifier $value = null) {
        $options = [];
        foreach ($this->getOptions() as $key => $caption) {
            $attributes = [
                'value' => $key
            ];

            if ($value && $key == $value->getKey()) {
                $attributes['selected'] = 'selected';
            }

            $options[] = new Element('option', $attributes, [
                $caption
            ]);
        }
        return $options;
    }

    private function getOptions() {
        /** @var IdentifierOptionsList $optionsList */
        $optionsList = $this->app->execute(new Query($this->listClass));
        return $optionsList->options();
    }
}