<?php
namespace rtens\udity\ui;

use rtens\domin\delivery\web\WebField;
use rtens\domin\Parameter;
use rtens\udity\AggregateIdentifier;
use rtens\udity\app\ui\fields\IdentifierEnumerationField;
use rtens\udity\app\ui\fields\IdentifierField;
use rtens\udity\domain\objects\DomainObject;
use rtens\udity\domain\query\IdentifierOptionsList;
use rtens\udity\Event;
use rtens\udity\Specification;
use rtens\udity\utils\Str;
use watoki\reflect\type\ClassType;

class SelectEntitiesFromIdentifierSpec extends Specification {

    function wrongName() {
        $class = $this->define('FooBaz', AggregateIdentifier::class);

        try {
            $this->runApp();
            $this->domin->fields->getField(new Parameter('that', new ClassType($class)));
        } catch (\Exception $exception) {
            $this->assert(Str::g($exception->getMessage())->startsWith('No field found'));
            return;
        }

        $this->assert->fail();
    }

    function noOptions() {
        $this->define('FooIdentifier', AggregateIdentifier::class);

        $field = $this->getIdentifierField('Foo');

        $this->assert->isInstanceOf($field, IdentifierField::class);
        $this->assert($field->inflate($this->parameter('Foo'), ['key' => 'one']), $this->id('Foo', 'one'));

        $rendered = $field->render($this->parameter('Foo', 'that'), null);
        $this->assert->contains($rendered, '<input class="form-control" type="text" name="that[key]" value=""/>');

        $rendered = $field->render($this->parameter('Foo', 'that'), $this->id('one'));
        $this->assert->contains($rendered, 'value="one"');
    }

    function noOptionsDisabled() {
        $this->define('FooIdentifier', AggregateIdentifier::class);

        $field = $this->getIdentifierField('Foo');
        $field->inflate($this->parameter('Foo', 'that'), ['key' => 'one', 'fix' => true]);

        $rendered = $field->render($this->parameter('Foo', 'that'), $this->id('Foo', 'one'));
        $this->assert->contains($rendered, 'name="" value="one" disabled="disabled"');
        $this->assert->contains($rendered, '<input type="hidden" name="that[key]" value="one"/>');
    }

    function disableIdentifierField() {
        $this->define('Foo', DomainObject::class);

        $field = $this->getIdentifierField('Foo');
        $field->inflate($this->parameter('Foo', 'that'), ['key' => 'one', 'fix' => true]);

        $this->assert->contains($field->render($this->parameter('Foo', 'that'), $this->id('Foo', 'one')),
            '<input type="hidden" name="that[key]" value="one"/>');

        $this->assert->contains($field->render($this->parameter('Foo', 'that'), $this->id('Foo', 'one')),
            'name="" disabled="disabled"');
        $this->assert->not()->contains($field->render($this->parameter('Foo', 'this'), $this->id('Foo', 'one')),
            'disabled="disabled"');
        $this->assert->not()->contains($field->render($this->parameter('Foo', 'that'), $this->id('Foo', 'two')),
            'disabled="disabled"');
    }

    function getOptionsFromList() {
        $this->define('FooIdentifier', AggregateIdentifier::class);
        $this->define('FooList', \stdClass::class, '
            function apply(\\' . Event::class . ' $event) {}
            function options() { return ["foo" => "bar"]; }
        ', IdentifierOptionsList::class);

        $field = $this->getIdentifierField('Foo');

        $this->assert->isInstanceOf($field, IdentifierEnumerationField::class);
        $rendered = $field->render($this->parameter('Foo'), $this->id('Foo'));
        $this->assert->contains($rendered, '<select class="form-control" name="bla[key]">');
        $this->assert->contains($rendered, '<option value="foo">bar</option>');
    }

    function showOptionsForDomainObject() {
        $this->define('Foo', DomainObject::class);

        $this->recordThat('Foo', 'one', 'Created');
        $this->recordThat('Foo', 'two', 'Created');
        $this->recordThat('Bar', 'three', 'Created');

        $field = $this->getIdentifierField('Foo');

        $rendered = $field->render($this->parameter('Foo'), null);
        $this->assert->contains($rendered, '<option value="one">one</option>');
        $this->assert->contains($rendered, '<option value="two">two</option>');
        $this->assert->not()->contains($rendered, '<option value="three">three</option>');
    }

    function selectedOptionForDomainObject() {
        $this->define('Foo', DomainObject::class);

        $this->recordThat('Foo', 'one', 'Created');
        $this->recordThat('Foo', 'two', 'Created');

        $field = $this->getIdentifierField('Foo');

        $rendered = $field->render($this->parameter('one'), $this->id('Foo', 'two'));
        $this->assert->contains($rendered, '<option value="two" selected="selected">two</option>');
    }

    function withDomainObjectCaption() {
        $this->define('Foo', DomainObject::class, '
            function caption() { return "My Caption"; }
        ');

        $this->recordThat('Foo', 'one', 'Created');

        $field = $this->getIdentifierField('Foo');

        $rendered = $field->render($this->parameter('Foo'), $this->id('Foo'));
        $this->assert->contains($rendered, '<option value="one">My Caption</option>');
    }

    /**
     * @return WebField
     */
    private function getIdentifierField($class) {
        $parameter = $this->parameter($class);
        $this->runApp();
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->domin->fields->getField($parameter);
    }

    /**
     * @param $class
     * @param string $name
     * @return Parameter
     */
    private function parameter($class, $name = 'bla') {
        return new Parameter($name, new ClassType(get_class($this->id($class))));
    }
}