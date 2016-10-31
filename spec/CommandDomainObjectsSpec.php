<?php
namespace spec\rtens\proto;

use rtens\proto\DomainObject;
use rtens\proto\Event;
use rtens\scrut\Assert;

class CommandDomainObjectsSpec extends Specification {

    function create(Assert $assert) {
        $this->define('EmptyObject', DomainObject::class, '
            function create() {}
        ');
        $objectClass = 'proto\test\domain\EmptyObject';

        $this->execute('EmptyObject$create');

        $assert($this->domin->actions->getAction('EmptyObject$create')->parameters(), []);

        /** @var Event[] $allEvents */
        $allEvents = $this->events->allEvents();
        $assert($allEvents[0]->getName(), 'Created');
        $assert($allEvents[0]->getAggregateIdentifier()->getAggregateName(), $objectClass);
    }
}