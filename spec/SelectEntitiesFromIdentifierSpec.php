<?php
namespace rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\app\ui\IdentifierEnumerationField;
use rtens\proto\app\ui\IdentifierField;
use rtens\proto\domain\objects\DomainObject;
use rtens\proto\domain\query\IdentifierOptionsList;
use watoki\reflect\type\ClassType;

class SelectEntitiesFromIdentifierSpec extends Specification {

    function showOptionsForDomainObject() {
        $this->define('Foo', DomainObject::class);
        $parameter = new Parameter('bla', new ClassType(get_class($this->id('Foo'))));

        $this->recordThat('Foo', 'one', 'Created');
        $this->recordThat('Foo', 'two', 'Created');
        $this->recordThat('Bar', 'three', 'Created');

        $this->runApp();
        $field = $this->domin->fields->getField($parameter);

        $this->assert->isInstanceOf($field, IdentifierEnumerationField::class);

        /** @var IdentifierEnumerationField $field */
        $rendered = $field->render($parameter, null);
        $this->assert->contains($rendered, '<option value="one">one</option>');
        $this->assert->contains($rendered, '<option value="two">two</option>');
        $this->assert->not()->contains($rendered, '<option value="three">three</option>');
    }

    function selectOptionForDomainObject() {
        $this->define('Foo', DomainObject::class);
        $parameter = new Parameter('bla', new ClassType(get_class($this->id('Foo'))));

        $this->recordThat('Foo', 'one', 'Created');
        $this->recordThat('Foo', 'two', 'Created');

        $this->runApp();
        $field = $this->domin->fields->getField($parameter);

        /** @var IdentifierEnumerationField $field */
        $rendered = $field->render($parameter, $this->id('Foo', 'two'));
        $this->assert->contains($rendered, '<option value="two" selected="selected">two</option>');
    }

    function withDomainObjectCaption() {
        $this->define('Foo', DomainObject::class, '
            function caption() { return "My Caption"; }
        ');
        $parameter = new Parameter('bla', new ClassType(get_class($this->id('Foo'))));

        $this->recordThat('Foo', 'one', 'Created');

        $this->runApp();
        $field = $this->domin->fields->getField($parameter);

        /** @var IdentifierEnumerationField $field */
        $rendered = $field->render($parameter, $this->id('Foo'));
        $this->assert->contains($rendered, '<option value="one">My Caption</option>');
    }

    function withoutProjectionList() {
        $this->define('FooIdentifier', AggregateIdentifier::class);
        $parameter = new Parameter('bla', new ClassType(get_class($this->id('Foo'))));

        $this->recordThat('Foo', 'one', 'Created');

        $this->runApp();
        $field = $this->domin->fields->getField($parameter);

        $this->assert->isInstanceOf($field, IdentifierField::class);
    }

    function withOptions() {
        $this->define('FooIdentifier', AggregateIdentifier::class);
        $this->define('FooList', \stdClass::class, '
            function apply(\\' .Event::class . ' $event) {}
            function getOptions() { return ["foo" => "bar"]; }
        ', IdentifierOptionsList::class);

        $parameter = new Parameter('bla', new ClassType(get_class($this->id('Foo'))));

        $this->recordThat('Foo', 'one', 'Created');

        $this->runApp();
        $field = $this->domin->fields->getField($parameter);

        /** @var IdentifierEnumerationField $field */
        $rendered = $field->render($parameter, $this->id('Foo'));
        $this->assert->contains($rendered, '<option value="foo">bar</option>');
    }
}