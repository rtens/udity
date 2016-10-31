<?php
namespace spec\rtens\proto;

use rtens\domin\delivery\web\WebApplication;
use rtens\proto\Application;
use rtens\proto\DomainObject;
use rtens\proto\Event;
use rtens\proto\Time;
use rtens\scrut\Assert;
use watoki\factory\Factory;
use watoki\karma\stores\EventStore;
use watoki\karma\stores\MemoryEventStore;

class CommandDomainObjectsSpec {

    /**
     * @var EventStore
     */
    private $events;
    /**
     * @var WebApplication
     */
    private $domin;

    public function before() {
        Time::freeze();

        $this->events = new MemoryEventStore();
        $this->domin = (new Factory())->getInstance(WebApplication::class);
    }

    private function execute($objectClass, $method, $arguments = []) {
        $this->runApp();
        return $this->domin->actions->getAction($objectClass . '$' . $method)->execute($arguments);
    }

    private function runApp() {
        $app = new Application($this->events);
        $app->run($this->domin);
    }

    ##############################################################################################

    function create(Assert $assert) {
        eval('namespace proto\test\domainObject;
        class EmptyObject extends \\' . DomainObject::class . ' {
            function create() {}
        }');
        $objectClass = 'proto\test\domainObject\EmptyObject';

        $this->execute('EmptyObject', 'create');

        $assert($this->domin->actions->getAction('EmptyObject$create')->parameters(), []);

        /** @var Event[] $allEvents */
        $allEvents = $this->events->allEvents();
        $assert($allEvents[0]->getName(), 'Created');
        $assert($allEvents[0]->getAggregateIdentifier()->getAggregateName(), $objectClass);
    }
}