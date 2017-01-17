<?php
namespace rtens\udity\domain\objects;

use rtens\domin\Parameter;
use rtens\udity\Specification;
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

    function doNotRegisterDomainObjectItself() {
        $this->define('Foo', DomainObject::class);

        $this->runApp();
        $this->assert->not()->contains($this->actionIds(), 'DomainObject');
    }
}