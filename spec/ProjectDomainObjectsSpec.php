<?php
namespace rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\domain\objects\DomainObject;
use rtens\proto\domain\objects\DomainObjectList;
use watoki\reflect\type\ClassType;

class ProjectDomainObjectsSpec extends Specification {

    function emptyObject() {
        $class = $this->define('Object', DomainObject::class);

        $object = $this->execute('Object', [
            'identifier' => $this->id('Object', 'foo')
        ]);

        $this->assert($this->action('Object')->parameters(), [
            new Parameter('identifier', new ClassType($class . 'Identifier'), true)
        ]);

        $this->assert(is_object($object));
        $this->assert(get_class($object), $class);
    }

    function withCreatedArguments() {
        $this->define('Object', DomainObject::class, '
            function created($one, $two) {
                $this->createdWith = $one . $two;
            }
        ');

        $this->recordThat('Object', 'foo', 'Created', [
            'one' => 'Bar',
            'two' => 'Baz'
        ]);

        $this->recordThat('Wrong', 'foo', 'Created');

        $object = $this->execute('Object', [
            'identifier' => $this->id('Object', 'foo')
        ]);
        $this->assert($object->createdWith, 'BarBaz');
    }

    function projectAll() {
        $this->define('Object', DomainObject::class, '
            function created($name) {
                $this->name = $name;
            }
        ');

        $this->recordThat('Object', 'one', 'Created', ['name' => 'One']);
        $this->recordThat('Object', 'two', 'Created', ['name' => 'Two']);
        $this->recordThat('Object', 'three', 'Created', ['name' => 'Three']);

        $this->recordThat('Wrong', 'three', 'Created');

        /** @var DomainObjectList $objects */
        $objects = $this->execute('ObjectList$all');

        $this->assert(count($objects->getList()), 3);
        $this->assert(is_object($objects->getList()[0]));
        $this->assert($objects->getList()[0]->name, 'One');
        $this->assert($objects->getList()[1]->name, 'Two');
        $this->assert($objects->getList()[2]->name, 'Three');
    }

    function doNotRegisterDomainObjectItself() {
        $this->define('Foo', DomainObject::class);

        $this->runApp();
        $this->assert->not()->contains($this->actionIds(), 'DomainObject');
    }
}