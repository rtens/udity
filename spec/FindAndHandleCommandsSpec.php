<?php
namespace spec\rtens\proto;

use rtens\domin\delivery\web\WebApplication;
use rtens\domin\Parameter;
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
use watoki\reflect\type\StringType;

class FindAndHandleCommandsSpec {

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

    function aggregateDoesNotExist(Assert $assert) {
        try {
            $this->handle('Foo', 'Bar');
            $assert->fail();
        } catch (\Exception $exception) {
            $assert($exception->getMessage(), 'Action [Foo$Bar] is not registered.');
        }
    }

    function noMethods(Assert $assert) {
        eval('namespace proto\test\domain;
        class NoMethods extends \\' . AggregateRoot::class . ' {
        }');

        $this->runApp();
        $assert($this->domin->actions->getAllActions(), []);
    }

    function nothingHappens(Assert $assert) {
        eval('namespace proto\test\domain;
        class Nothing extends \\' . AggregateRoot::class . ' {
            function handleFoo() {}
        }');

        $this->handle('Nothing', 'Foo');
        $assert($this->recordedEvents(), []);
    }

    function appendEvents(Assert $assert) {
        eval('namespace proto\test\domain;
        class AppendEvents extends \\' . AggregateRoot::class . ' {
            function handleFoo() {
                $this->recordThat("This happened");
            }
        }');

        $this->handle('AppendEvents', 'Foo', [
            CommandAction::AGGREGATE_IDENTIFIER_KEY => 'baz'
        ]);

        $assert($this->recordedEvents(), [
            new Event(
                $this->id('AppendEvents', 'baz'),
                'This happened'
            )
        ]);
    }

    function withArguments(Assert $assert) {
        eval('namespace proto\test\domain;
        class WithArguments extends \\' . AggregateRoot::class . ' {
            function handleFoo($two, $one) {
                $this->recordThat("This happened", ["this" => $one . $two]);
            }
        }');

        $this->handle('WithArguments', 'Foo', ['one' => 'And', 'two' => 'That']);

        $assert($this->recordedEvents()[0]->getArguments(), ['this' => 'AndThat']);
    }

    function applyEvents(Assert $assert) {
        eval('namespace proto\test\domain;
        class ApplyEvents extends \\' . AggregateRoot::class . ' {
            function applyThat($two, \\' . Event::class . ' $e, $one) {
                $this->applied = $e->getName() . $one . $two;
            }
            function handleFoo() {
                $this->recordThat("Applied", [$this->applied]);
            }
        }');

        $this->events->append(new Event($this->id('ApplyEvents'), 'That', ['one' => 'And', 'two' => 'This']), $this->id('ApplyEvents'));

        $this->handle('ApplyEvents', 'Foo');

        $assert($this->recordedEvents()[1]->getName(), 'Applied');
        $assert($this->recordedEvents()[1]->getArguments(), ['ThatAndThis']);
    }


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
}