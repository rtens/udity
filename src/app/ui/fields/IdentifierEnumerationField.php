<?php
namespace rtens\proto\app\ui\fields;

use rtens\domin\delivery\web\Element;
use rtens\domin\Parameter;
use rtens\proto\AggregateIdentifier;
use rtens\proto\app\Application;
use rtens\proto\Query;

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
        return (string)new Element('select', [
            'name' => $parameter->getName(),
            'class' => 'form-control'
        ], $this->renderOptions($value));
    }

    private function renderOptions(AggregateIdentifier $value = null) {
        $options = [];
        foreach ($this->getOptions() as $key => $caption) {
            $options[] = new Element('option', array_merge([
                'value' => $key
            ], $value && $key == $value->getKey() ? [
                'selected' => 'selected'
            ] : []), [
                $caption
            ]);
        }
        return $options;
    }

    private function getOptions() {
        return $this->app->execute(new Query($this->listClass))->getOptions();
    }
}