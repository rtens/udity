<?php
namespace spec\rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\DomainObject;
use rtens\proto\Event;
use rtens\scrut\Assert;
use watoki\reflect\type\StringType;

class ProjectDomainObjectsSpec extends Specification {

    function emptyObject(Assert $assert) {
        $class = $this->define('SomeEmptyObject', DomainObject::class);

        $object = $this->execute('SomeEmptyObject$read', [
            'identifier' => 'foo'
        ]);

        $assert($this->domin->actions->getAction('SomeEmptyObject$read')->parameters(), [
            new Parameter('identifier', new StringType(), true)
        ]);

        $assert(is_object($object));
        $assert(get_class($object), $class);
    }

    function withCreatedArguments(Assert $assert) {
        $this->define('WithCreatedArguments', DomainObject::class, '
            function created($one, $two) {
                $this->createdWith = $one . $two;
            }
        ');

        $this->events->append(new Event($this->id('WithCreatedArguments', 'foo'), 'Created', [
            'one' => 'Bar',
            'two' => 'Baz'
        ]), $this->id('WithCreatedArguments', 'foo'));

        $object = $this->execute('WithCreatedArguments$read', [
            'identifier' => 'foo'
        ]);
        $assert($object->createdWith, 'BarBaz');
    }

    function projectAll(Assert $assert) {
        $this->define('ProjectAll', DomainObject::class, '
            function created($name) {
                $this->name = $name;
            }
        ');

        $this->events->append(new Event($this->id('ProjectAll', 'one'), 'Created', ['name' => 'One']),
            $this->id('ProjectAll', 'one'));
        $this->events->append(new Event($this->id('ProjectAll', 'two'), 'Created', ['name' => 'Two']),
            $this->id('ProjectAll', 'two'));
        $this->events->append(new Event($this->id('ProjectAll', 'three'), 'Created', ['name' => 'Three']),
            $this->id('ProjectAll', 'three'));

        $this->events->append(new Event($this->id('Wrong', 'three'), 'Created'),
            $this->id('Wrong', 'three'));

        $objects = $this->execute('ProjectAll$all');

        $assert(count($objects->getAll()), 3);
        $assert(is_object($objects->getAll()[0]));
        $assert($objects->getAll()[0]->name, 'One');
        $assert($objects->getAll()[1]->name, 'Two');
        $assert($objects->getAll()[2]->name, 'Three');
    }
}