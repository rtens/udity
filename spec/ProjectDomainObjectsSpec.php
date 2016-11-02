<?php
namespace spec\rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\DomainObject;
use rtens\proto\Event;
use watoki\reflect\type\ClassType;

class ProjectDomainObjectsSpec extends Specification {

    function emptyObject() {
        $class = $this->define('SomeEmptyObject', DomainObject::class);

        $object = $this->execute('SomeEmptyObject', [
            'identifier' => $this->id('SomeEmptyObject', 'foo')
        ]);

        $this->assert($this->domin->actions->getAction('SomeEmptyObject')->parameters(), [
            new Parameter('identifier', new ClassType($class . 'Identifier'), true)
        ]);

        $this->assert(is_object($object));
        $this->assert(get_class($object), $class);
    }

    function withCreatedArguments() {
        $this->define('WithCreatedArguments', DomainObject::class, '
            function created($one, $two) {
                $this->createdWith = $one . $two;
            }
        ');

        $this->events->append(new Event($this->id('WithCreatedArguments', 'foo'), 'Created', [
            'one' => 'Bar',
            'two' => 'Baz'
        ]), $this->id('WithCreatedArguments', 'foo'));

        $this->events->append(new Event($this->id('Wrong', 'foo'), 'Created'),
            $this->id('Wrong', 'foo'));

        $object = $this->execute('WithCreatedArguments', [
            'identifier' => $this->id('WithCreatedArguments', 'foo')
        ]);
        $this->assert($object->createdWith, 'BarBaz');
    }

    function projectAll() {
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

        $objects = $this->execute('ProjectAllList$all');

        $this->assert(count($objects->getAll()), 3);
        $this->assert(is_object($objects->getAll()[0]));
        $this->assert($objects->getAll()[0]->name, 'One');
        $this->assert($objects->getAll()[1]->name, 'Two');
        $this->assert($objects->getAll()[2]->name, 'Three');
    }
}