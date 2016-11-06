<?php
namespace rtens\proto;

use rtens\domin\delivery\web\WebField;
use rtens\domin\Parameter;
use rtens\proto\app\ui\fields\IdentifierEnumerationField;
use rtens\proto\app\ui\fields\IdentifierField;
use rtens\proto\domain\objects\DomainObject;
use rtens\proto\domain\query\IdentifierOptionsList;
use rtens\proto\utils\Str;
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
        $this->assert($rendered, '<input class="form-control" type="text" name="that[key]" value=""/>');

        $rendered = $field->render($this->parameter('Foo', 'that'), $this->id('one'));
        $this->assert->contains($rendered, 'value="one"');
    }

    function disableIdentifierField() {
        $this->define('Foo', DomainObject::class);

        $field = $this->getIdentifierField('Foo');
        $field->inflate($this->parameter('Foo', 'that'), ['key' => 'one', 'fix' => true]);

        $this->assert->contains($field->render($this->parameter('Foo', 'that'), $this->id('Foo', 'one')),
            'disabled="disabled"');
        $this->assert->not()->contains($field->render($this->parameter('Foo', 'this'), $this->id('Foo', 'one')),
            'disabled="disabled"');
        $this->assert->not()->contains($field->render($this->parameter('Foo', 'that'), $this->id('Foo', 'two')),
            'disabled="disabled"');
    }

    function getOptionsFromList() {
        $this->define('FooIdentifier', AggregateIdentifier::class);
        $this->define('FooList', \stdClass::class, '
            function apply(\\' . Event::class . ' $event) {}
            function getOptions() { return ["foo" => "bar"]; }
        ', IdentifierOptionsList::class);

        $field = $this->getIdentifierField('Foo');

        $this->assert->isInstanceOf($field, IdentifierEnumerationField::class);
        $rendered = $field->render($this->parameter('Foo'), $this->id('Foo'));
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