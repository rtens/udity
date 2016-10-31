<?php
namespace spec\rtens\proto;

use rtens\proto\AggregateRoot;
use rtens\proto\CommandAction;
use rtens\proto\Event;
use rtens\scrut\Assert;

class CommandAggregatesSpec extends Specification {

    function aggregateDoesNotExist(Assert $assert) {
        try {
            $this->execute('Foo$Bar');
            $assert->fail();
        } catch (\Exception $exception) {
            $assert->pass();
        }
    }

    function noMethods(Assert $assert) {
        $this->define('NoMethods', AggregateRoot::class);

        try {
            $this->execute('NoMethods');
            $assert->fail();
        } catch (\Exception $exception) {
            $assert->pass();
        }
    }

    function nothingHappens(Assert $assert) {
        $this->define('Nothing', AggregateRoot::class, '
            function handleFoo() {}
        ');

        $this->execute('Nothing$Foo');
        $assert($this->recordedEvents(), []);
    }

    function appendEvents(Assert $assert) {
        $this->define('AppendEvents', AggregateRoot::class, '
            function handleFoo() {
                $this->recordThat("This happened");
            }
        ');

        $this->execute('AppendEvents$Foo', [
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
        $this->define('WithArguments', AggregateRoot::class, '
            function handleFoo($two, $one) {
                $this->recordThat("This happened", ["this" => $one . $two]);
            }
        ');

        $this->execute('WithArguments$Foo', ['one' => 'And', 'two' => 'That']);

        $assert($this->recordedEvents()[0]->getArguments(), ['this' => 'AndThat']);
    }

    function applyEvents(Assert $assert) {
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
            CommandAction::AGGREGATE_IDENTIFIER_KEY => 'baz'
        ]);

        $assert($this->recordedEvents()[1]->getName(), 'Applied');
        $assert($this->recordedEvents()[1]->getArguments(), ['ThatAndThis']);
    }
}