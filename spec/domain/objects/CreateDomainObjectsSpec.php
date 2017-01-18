<?php
namespace rtens\udity\domain\objects;

use rtens\domin\Parameter;
use rtens\udity\Specification;
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

    function actionFromHandler() {
        $this->define('Foo', DomainObject::class, '
            function create($one, $two = "default") {}
            function created($one, $two) {}
        ');

        $this->execute('Foo$create', ['one' => 'uno']);
        $this->assert(count($this->recordedEvents()), 1);
        $this->assert($this->recordedEvents()[0]->getPayload(), [
            'one' => 'uno',
            'two' => 'default'
        ]);
    }

    function prohibitCreation() {
        $this->define('Foo', DomainObject::class, '
            function create() {
                throw new \Exception();
            }
        ');

        try {
            $caught = null;
            $this->execute('Foo$create');
        } catch (\Exception $exception) {
            $caught = $exception;
        }

        $this->assert->not()->isNull($caught);
        $this->assert(count($this->recordedEvents()), 0);
    }
}