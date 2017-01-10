<?php
namespace rtens\udity;

use rtens\udity\app\ui\actions\AggregateCommandAction;
use rtens\udity\domain\command\Aggregate;
use rtens\udity\domain\command\Singleton;

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
        $this->define('Foo', Aggregate::class);

        $this->assert($this->actionIds(), []);
    }

    function nothingHappens() {
        $this->define('Root', Aggregate::class, '
            function handleFoo() {}
        ');

        $this->execute('Root$Foo', [
            AggregateCommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);
        $this->assert($this->recordedEvents(), []);

        $this->assert($this->domin->actions->getAction('Root$Foo')->caption(), 'Foo');
        $this->assert(array_keys($this->domin->groups->getActionsOf('Root')), ['Root$Foo']);
    }

    function presentation() {
        $this->define('Root', Aggregate::class, '
            function handleFooThat() {}
        ');
        $this->runApp();

        $this->assert($this->domin->actions->getAction('Root$FooThat')->caption(), 'Foo That');
        $this->assert(array_keys($this->domin->groups->getActionsOf('Root')), ['Root$FooThat']);
    }

    function appendEvents() {
        $this->define('Root', Aggregate::class, '
            function handleFoo() {
                $this->recordThat("This happened");
            }
        ');

        $this->execute('Root$Foo', [
            AggregateCommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);

        $this->assert($this->recordedEvents(), [
            new Event(
                $this->id('Root', 'baz'),
                'This happened'
            )
        ]);
    }

    function withArguments() {
        $this->define('Root', Aggregate::class, '
            function handleFoo($two, $one) {
                $this->recordThat("This happened", ["this" => $one . $two]);
            }
        ');

        $this->execute('Root$Foo', [
            AggregateCommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz'),
            'one' => 'And',
            'two' => 'That'
        ]);

        $this->assert($this->recordedEvents()[0]->getPayload(), ['this' => 'AndThat']);
    }

    function withDefaultArguments() {
        $this->define('Root', Aggregate::class, '
            function handleFoo($one = "uno", $two = "dos") {
                $this->recordThat("This", ["one" => $one, "two" => $two]);
            }
        ');

        $this->execute('Root$Foo', [
            AggregateCommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);
        $this->assert($this->recordedEvents()[0]->getPayload(), ['one' => 'uno', 'two' => 'dos']);

        $this->assert($this->action('Root$Foo')->fill([]), [
            'one' => 'uno',
            'two' => 'dos'
        ]);
    }

    function applyEvents() {
        $this->define('Root', Aggregate::class, '
            function applyThat($two, \\' . Event::class . ' $e, $one) {
                $this->applied = $e->getName() . $one . $two;
            }
            function handleFoo() {
                $this->recordThat("Applied", [$this->applied]);
            }
        ');

        $this->recordThat('Root', 'baz', 'That', ['one' => 'And', 'two' => 'This']);

        $this->execute('Root$Foo', [
            AggregateCommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);

        $this->assert($this->recordedEvents()[1]->getName(), 'Applied');
        $this->assert($this->recordedEvents()[1]->getPayload(), ['ThatAndThis']);
    }

    function singleton() {
        $this->define('Root', Singleton::class, '
            function handleFoo() {}
        ');

        $this->execute('Root$Foo');
        $this->assert($this->recordedEvents(), []);
    }
}