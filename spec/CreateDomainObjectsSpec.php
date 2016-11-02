<?php
namespace spec\rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\DomainObject;
use watoki\reflect\type\StringType;

class CreateDomainObjectsSpec extends Specification {

    function emptyObject() {
        $this->define('NoCreateMethod', DomainObject::class);

        try {
            $this->execute('NoArguments$create');
            $this->assert->fail();
        } catch (\Exception $exception) {
            $this->assert->pass();
        }
    }

    function noArguments() {
        $objectClass = $this->define('NoArguments', DomainObject::class, '
            function created() {}
        ');

        $this->execute('NoArguments$create');

        $this->assert($this->domin->actions->getAction('NoArguments$create')->parameters(), []);

        $this->assert(count($this->recordedEvents()), 1);
        $this->assert($this->recordedEvents()[0]->getName(), 'Created');
        $this->assert($this->recordedEvents()[0]->getAggregateIdentifier()->getAggregateName(), $objectClass);
    }

    function createWithArguments() {
        $this->define('WithArguments', DomainObject::class, '
            function created($one, $two) {}
        ');

        $this->execute('WithArguments$create', [
            'one' => 'Bar',
            'two' => 'Baz'
        ]);

        $this->assert($this->domin->actions->getAction('WithArguments$create')->parameters(), [
            new Parameter('one', new StringType(), true),
            new Parameter('two', new StringType(), true),
        ]);
    }
}