<?php
namespace rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\domain\objects\DomainObject;
use watoki\reflect\type\ClassType;

class ProjectDomainObjectsSpec extends Specification {

    public function before() {
        $this->assert->incomplete('tabula rasa');
    }

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

        $objects = $this->execute('ObjectList$all');

        $this->assert(count($objects->getAll()), 3);
        $this->assert(is_object($objects->getAll()[0]));
        $this->assert($objects->getAll()[0]->name, 'One');
        $this->assert($objects->getAll()[1]->name, 'Two');
        $this->assert($objects->getAll()[2]->name, 'Three');
    }

    function doNotRegisterDomainObjectItself() {
        $this->define('Foo', DomainObject::class);

        $this->runApp();
        $this->assert->not()->contains($this->actionIds(), 'DomainObject');
    }
}