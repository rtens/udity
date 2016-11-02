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
        $class = $this->define('AddIdentifierProperty', AggregateRoot::class, '
            function handleFoo() {
                $this->recordThat("This happened", [$this->identifier]);
            }
        ');

        $this->execute('AddIdentifierProperty$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('AddIdentifierProperty', 'baz')
        ]);

        $this->assert($this->domin->actions->getAction('AddIdentifierProperty$Foo')->parameters(), [
            new Parameter(CommandAction::IDENTIFIER_KEY, new ClassType($class . 'Identifier'), true)
        ]);

        $this->assert($this->recordedEvents(), [
            new Event(
                $this->id('AddIdentifierProperty', 'baz'),
                'This happened',
                [$this->id('AddIdentifierProperty', 'baz')]
            )
        ]);
    }


    function singletonAggregate() {
        $this->define('Singleton', SingletonAggregateRoot::class, '
            function handleFoo() {
                $this->recordThat("This happened", [$this->identifier]);
            }
        ');

        $this->execute('Singleton$Foo', [
            CommandAction::IDENTIFIER_KEY => $this->id('Singleton', 'baz')
        ]);

        $this->assert($this->domin->actions->getAction('Singleton$Foo')->parameters(), []);
        $this->assert($this->recordedEvents(), [
            new Event(
                $this->id('Singleton'),
                'This happened',
                [$this->id('Singleton')]
            )
        ]);
    }

    function staticIdentifier() {
        $this->define('StaticIdentifier', AggregateRoot::class, '
            function handleFoo() {
                $this->recordThat("This happened", [$this->identifier]);
            }
        ');
        $identifierClass = $this->define('StaticIdentifierIdentifier', AggregateIdentifier::class);

        $this->execute('StaticIdentifier$Foo', [
            CommandAction::IDENTIFIER_KEY => new $identifierClass('bar')
        ]);

        $this->assert($this->domin->actions->getAction('StaticIdentifier$Foo')->parameters(), [
            new Parameter(CommandAction::IDENTIFIER_KEY, new ClassType($identifierClass), true)
        ]);

        $this->assert(get_class($this->recordedEvents()[0]->getAggregateIdentifier()),
            $identifierClass);
    }
}