<?php
namespace spec\rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\DomainObject;
use rtens\scrut\Assert;
use watoki\reflect\type\StringType;

class CreateDomainObjectsSpec extends Specification {

    function emptyObject(Assert $assert) {
        $this->define('NoCreateMethod', DomainObject::class);

        try {
            $this->execute('NoArguments$create');
            $assert->fail();
        } catch (\Exception $exception) {
            $assert->pass();
        }
    }

    function noArguments(Assert $assert) {
        $objectClass = $this->define('NoArguments', DomainObject::class, '
            function created() {}
        ');

        $this->execute('NoArguments$create');

        $assert($this->domin->actions->getAction('NoArguments$create')->parameters(), []);

        $assert(count($this->recordedEvents()), 1);
        $assert($this->recordedEvents()[0]->getName(), 'Created');
        $assert($this->recordedEvents()[0]->getAggregateIdentifier()->getAggregateName(), $objectClass);
    }

    function createWithArguments(Assert $assert) {
        $this->define('WithArguments', DomainObject::class, '
            function created($one, $two) {}
        ');

        $this->execute('WithArguments$create', [
            'one' => 'Bar',
            'two' => 'Baz'
        ]);

        $assert($this->domin->actions->getAction('WithArguments$create')->parameters(), [
            new Parameter('one', new StringType(), true),
            new Parameter('two', new StringType(), true),
        ]);
    }
}