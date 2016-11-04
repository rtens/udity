<?php
namespace rtens\proto;

use rtens\proto\app\ui\CommandAction;
use rtens\proto\domain\command\Aggregate;
use rtens\proto\domain\command\Singleton;

class HandleCommandsSpec extends Specification {

    public function before() {
        $this->assert->incomplete('tabula rasa');
    }

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
            CommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);
        $this->assert($this->recordedEvents(), []);
    }

    function singleton() {
        $this->define('Root', Singleton::class, '
            function handleFoo() {}
        ');

        $this->execute('Root$Foo');
        $this->assert($this->recordedEvents(), []);
    }

    function appendEvents() {
        $this->define('Root', Aggregate::class, '
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
        $this->define('Root', Aggregate::class, '
            function handleFoo($two, $one) {
                $this->recordThat("This happened", ["this" => $one . $two]);
            }
        ');

        $this->execute('Root$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz'),
            'one' => 'And',
            'two' => 'That'
        ]);

        $this->assert($this->recordedEvents()[0]->getPayload(), ['this' => 'AndThat']);
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
            CommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);

        $this->assert($this->recordedEvents()[1]->getName(), 'Applied');
        $this->assert($this->recordedEvents()[1]->getPayload(), ['ThatAndThis']);
    }
}