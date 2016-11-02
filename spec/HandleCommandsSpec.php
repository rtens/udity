<?php
namespace spec\rtens\proto;

use rtens\proto\AggregateRoot;
use rtens\proto\CommandAction;
use rtens\proto\Event;
use rtens\proto\SingletonAggregateRoot;

class HandleCommandsSpec extends Specification {

    function aggregateDoesNotExist() {
        try {
            $this->execute('Foo$Bar');
        } catch (\Exception $exception) {
            $this->assert->pass();
            return;
        }
        $this->assert->fail();
    }

    function noMethods() {
        $this->define('NoMethods', AggregateRoot::class);

        $this->assert($this->domin->actions->getAllActions(), []);
    }

    function nothingHappens() {
        $this->define('Nothing', AggregateRoot::class, '
            function handleFoo() {}
        ');

        $this->execute('Nothing$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('Nothing', 'baz')
        ]);
        $this->assert($this->recordedEvents(), []);
    }

    function singleton() {
        $this->define('SingletonAggregate', SingletonAggregateRoot::class, '
            function handleFoo() {}
        ');

        $this->execute('SingletonAggregate$Foo');
        $this->assert($this->recordedEvents(), []);
    }

    function appendEvents() {
        $this->define('AppendEvents', AggregateRoot::class, '
            function handleFoo() {
                $this->recordThat("This happened");
            }
        ');

        $this->execute('AppendEvents$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('AppendEvents', 'baz')
        ]);

        $this->assert($this->recordedEvents(), [
            new Event(
                $this->id('AppendEvents', 'baz'),
                'This happened'
            )
        ]);
    }

    function withArguments() {
        $this->define('WithArguments', AggregateRoot::class, '
            function handleFoo($two, $one) {
                $this->recordThat("This happened", ["this" => $one . $two]);
            }
        ');

        $this->execute('WithArguments$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('WithArguments', 'baz'),
            'one' => 'And',
            'two' => 'That'
        ]);

        $this->assert($this->recordedEvents()[0]->getArguments(), ['this' => 'AndThat']);
    }

    function applyEvents() {
        $this->define('ApplyEvents', AggregateRoot::class, '
            function applyThat($two, \\' . Event::class . ' $e, $one) {
                $this->applied = $e->getName() . $one . $two;
            }
            function handleFoo() {
                $this->recordThat("Applied", [$this->applied]);
            }
        ');

        $this->events->append(new Event($this->id('baz'), 'That', ['one' => 'And', 'two' => 'This']), $this->id('baz'));

        $this->execute('ApplyEvents$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('ApplyEvents', 'baz')
        ]);

        $this->assert($this->recordedEvents()[1]->getName(), 'Applied');
        $this->assert($this->recordedEvents()[1]->getArguments(), ['ThatAndThis']);
    }
}