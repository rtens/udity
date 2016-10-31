<?php
namespace spec\rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\DomainObject;
use rtens\scrut\Assert;
use watoki\reflect\type\StringType;

class CreateDomainObjectsSpec extends Specification {

    function emptyObject(Assert $assert) {
        $objectClass = $this->define('CreateEmptyObject', DomainObject::class);

        $this->execute('CreateEmptyObject$create');

        $assert($this->domin->actions->getAction('CreateEmptyObject$create')->parameters(), []);

        $assert(count($this->recordedEvents()), 1);
        $assert($this->recordedEvents()[0]->getName(), 'Created');
        $assert($this->recordedEvents()[0]->getAggregateIdentifier()->getAggregateName(), $objectClass);
    }

    function objectWithPublicProperties(Assert $assert) {
        $this->define('ObjectWithPublicProperties', DomainObject::class, '
            public $one;
            public $two;
        ');

        $this->execute('ObjectWithPublicProperties$create', [
            'two' => 'bar'
        ]);

        $assert($this->domin->actions->getAction('ObjectWithPublicProperties$create')->parameters(), [
            new Parameter('one', new StringType(), false),
            new Parameter('two', new StringType(), false),
        ]);

        $assert($this->recordedEvents()[0]->getArguments(), ['two' => 'bar']);
    }

    function constructorAndSetters(Assert $assert) {
        $this->define('ConstructorAndSetters', DomainObject::class, '
            function __construct($identifier, $one, $two) {}
            function setThree($three) {}
        ');

        $this->execute('ConstructorAndSetters$create', [
            'one' => 'Bar',
            'two' => 'Baz'
        ]);

        $assert($this->domin->actions->getAction('ConstructorAndSetters$create')->parameters(), [
            new Parameter('one', new StringType(), true),
            new Parameter('two', new StringType(), true),
            new Parameter('three', new StringType(), false),
        ]);
    }
}