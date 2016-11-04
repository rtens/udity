<?php
namespace rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\domain\objects\DomainObject;
use watoki\reflect\type\StringType;

class CreateDomainObjectsSpec extends Specification {

    function emptyObject() {
        $this->define('Foo', DomainObject::class);

        try {
            $this->execute('Foo$create');
        } catch (\Exception $exception) {
            $this->assert->pass();
            return;
        }
        $this->assert->fail();
    }

    function noArguments() {
        $objectClass = $this->define('Foo', DomainObject::class, '
            function created() {}
        ');

        $this->execute('Foo$create');

        $this->assert($this->action('Foo$create')->parameters(), []);

        $this->assert(count($this->recordedEvents()), 1);
        $this->assert($this->recordedEvents()[0]->getName(), 'Created');
        $this->assert($this->recordedEvents()[0]->getAggregateIdentifier()->getName(), $objectClass);
    }

    function parameters() {
        $this->define('Foo', DomainObject::class, '
            function created($one, $two) {}
        ');

        $this->execute('Foo$create', [
            'one' => 'Bar',
            'two' => 'Baz'
        ]);

        $this->assert($this->recordedEvents()[0]->getPayload(), [
            'one' => 'Bar',
            'two' => 'Baz'
        ]);
        $this->assert($this->action('Foo$create')->parameters(), [
            new Parameter('one', new StringType(), true),
            new Parameter('two', new StringType(), true),
        ]);
    }
}