<?php
namespace spec\rtens\proto;

use rtens\proto\AggregateRoot;
use rtens\proto\CommandAction;
use rtens\proto\Event;
use rtens\proto\SingletonAggregateRoot;

class HandleCommandsSpec extends Specification {

    function aggregateDoesNotExist() {
        try {
            $this->execute('Root$Bar');
        } catch (\Exception $exception) {
            $this->assert->pass();
            return;
        }
        $this->assert->fail();
    }

    function noMethods() {
        $this->define('Foo', AggregateRoot::class);

        $this->assert($this->domin->actions->getAllActions(), []);
    }

    function nothingHappens() {
        $this->define('Root', AggregateRoot::class, '
            function handleFoo() {}
        ');

        $this->execute('Root$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);
        $this->assert($this->recordedEvents(), []);
    }

    function singleton() {
        $this->define('Root', SingletonAggregateRoot::class, '
            function handleFoo() {}
        ');

        $this->execute('Root$Foo');
        $this->assert($this->recordedEvents(), []);
    }

    function appendEvents() {
        $this->define('Root', AggregateRoot::class, '
            function handleFoo() {
                $this->recordThat("This happened");
            }
        ');

        $this->execute('Root$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);

        $this->assert($this->recordedEvents(), [
            new Event(
                $this->id('Root', 'baz'),
                'This happened'
            )
        ]);
    }

    function withArguments() {
        $this->define('Root', AggregateRoot::class, '
            function handleFoo($two, $one) {
                $this->recordThat("This happened", ["this" => $one . $two]);
            }
        ');

        $this->execute('Root$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz'),
            'one' => 'And',
            'two' => 'That'
        ]);

        $this->assert($this->recordedEvents()[0]->getArguments(), ['this' => 'AndThat']);
    }

    function applyEvents() {
        $this->define('Root', AggregateRoot::class, '
            function applyThat($two, \\' . Event::class . ' $e, $one) {
                $this->applied = $e->getName() . $one . $two;
            }
            function handleFoo() {
                $this->recordThat("Applied", [$this->applied]);
            }
        ');

        $this->recordThat('Root', 'baz', 'That', ['one' => 'And', 'two' => 'This']);

        $this->execute('Root$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);

        $this->assert($this->recordedEvents()[1]->getName(), 'Applied');
        $this->assert($this->recordedEvents()[1]->getArguments(), ['ThatAndThis']);
    }
}