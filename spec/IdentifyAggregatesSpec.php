<?php
namespace spec\rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\AggregateIdentifier;
use rtens\proto\AggregateRoot;
use rtens\proto\CommandAction;
use rtens\proto\Event;
use rtens\proto\SingletonAggregateRoot;
use watoki\reflect\type\ClassType;

class IdentifyAggregatesSpec extends Specification {

    function addIdentifierProperty() {
        $class = $this->define('Root', AggregateRoot::class, '
            function handleFoo() {
                $this->recordThat("This happened", [$this->getIdentifier()]);
            }
        ');

        $this->execute('Root$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);

        $this->assert($this->action('Root$Foo')->parameters(), [
            new Parameter(CommandAction::IDENTIFIER_KEY, new ClassType($class . 'Identifier'), true)
        ]);

        $this->assert($this->recordedEvents(), [
            new Event(
                $this->id('Root', 'baz'),
                'This happened',
                [$this->id('Root', 'baz')]
            )
        ]);
    }


    function singletonAggregate() {
        $this->define('Root', SingletonAggregateRoot::class, '
            function handleFoo() {
                $this->recordThat("This happened", [$this->getIdentifier()]);
            }
        ');

        $this->execute('Root$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);

        $this->assert($this->action('Root$Foo')->parameters(), []);
        $this->assert($this->recordedEvents(), [
            new Event(
                $this->id('Root'),
                'This happened',
                [$this->id('Root')]
            )
        ]);
    }

    function staticIdentifier() {
        $this->define('Root', AggregateRoot::class, '
            function handleFoo() {
                $this->recordThat("This happened", [$this->getIdentifier()]);
            }
        ');
        $identifierClass = $this->define('RootIdentifier', AggregateIdentifier::class);

        $this->execute('Root$Foo', [
            CommandAction::IDENTIFIER_KEY => new $identifierClass('bar')
        ]);

        $this->assert($this->action('Root$Foo')->parameters(), [
            new Parameter(CommandAction::IDENTIFIER_KEY, new ClassType($identifierClass), true)
        ]);

        $this->assert(get_class($this->recordedEvents()[0]->getAggregateIdentifier()),
            $identifierClass);
    }
}