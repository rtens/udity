<?php
namespace spec\rtens\proto;

use rtens\domin\delivery\web\WebApplication;
use rtens\domin\Parameter;
use rtens\proto\AggregateIdentifier;
use rtens\proto\AggregateRoot;
use rtens\proto\Application;
use rtens\proto\CommandAction;
use rtens\proto\Event;
use rtens\proto\GenericAggregateIdentifier;
use rtens\proto\SingletonAggregateRoot;
use rtens\proto\Time;
use rtens\scrut\Assert;
use watoki\factory\Factory;
use watoki\karma\stores\EventStore;
use watoki\karma\stores\MemoryEventStore;
use watoki\reflect\type\ClassType;
use watoki\reflect\type\StringType;

class IdentifyAggregatesSpec {

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

    /**
     * @return mixed|Event[]
     */
    private function recordedEvents() {
        return $this->events->allEvents();
    }

    private function handle($aggregate, $command, $arguments = []) {
        $this->runApp();
        $this->domin->actions->getAction($aggregate . '$' . $command)->execute($arguments);
    }

    private function runApp() {
        $app = new Application($this->events);
        $app->run($this->domin);
    }

    private function id($aggregate, $key = null) {
        return new GenericAggregateIdentifier('proto\test\domain\\' . $aggregate, $key ?: $aggregate);
    }

    #################################################################################################

    function addIdentifierProperty(Assert $assert) {
        eval('namespace proto\test\domain;
        class AddIdentifierProperty extends \\' . AggregateRoot::class . ' {
            function handleFoo() {
                $this->recordThat("This happened", [$this->identifier]);
            }
        }');

        $this->handle('AddIdentifierProperty', 'Foo', [
            CommandAction::AGGREGATE_IDENTIFIER_KEY => 'baz'
        ]);

        $assert($this->domin->actions->getAction('AddIdentifierProperty$Foo')->parameters(), [
            new Parameter(CommandAction::AGGREGATE_IDENTIFIER_KEY, new StringType(), true)
        ]);

        $assert($this->recordedEvents(), [
            new Event(
                $this->id('AddIdentifierProperty', 'baz'),
                'This happened',
                [$this->id('AddIdentifierProperty', 'baz')]
            )
        ]);
    }


    function singletonAggregate(Assert $assert) {
        eval('namespace proto\test\domain;
        class Singleton extends \\' . SingletonAggregateRoot::class . ' {
            function handleFoo() {
                $this->recordThat("This happened", [$this->identifier]);
            }
        }');

        $this->handle('Singleton', 'Foo');

        $assert($this->domin->actions->getAction('Singleton$Foo')->parameters(), []);
        $assert($this->recordedEvents(), [
            new Event(
                $this->id('Singleton'),
                'This happened',
                [$this->id('Singleton')]
            )
        ]);
    }

    function staticIdentifier(Assert $assert) {
        $identifierClass = 'proto\test\domain\StaticIdentifierIdentifier';
        eval('namespace proto\test\domain;
        class StaticIdentifier extends \\' . AggregateRoot::class . ' {
            function handleFoo() {
                $this->recordThat("This happened", [$this->identifier]);
            }
        }
        
        class StaticIdentifierIdentifier extends \\' . AggregateIdentifier::class . ' {
        }');

        $this->handle('StaticIdentifier', 'Foo', [
            CommandAction::AGGREGATE_IDENTIFIER_KEY => new $identifierClass('bar')
        ]);

        $assert($this->domin->actions->getAction('StaticIdentifier$Foo')->parameters(), [
            new Parameter(CommandAction::AGGREGATE_IDENTIFIER_KEY, new ClassType($identifierClass), true)
        ]);

        $assert(get_class($this->recordedEvents()[0]->getAggregateIdentifier()),
            $identifierClass);
    }
}