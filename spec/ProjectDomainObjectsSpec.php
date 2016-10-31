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
}