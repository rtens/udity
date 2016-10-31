<?php
namespace spec\rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\AggregateIdentifier;
use rtens\proto\AggregateRoot;
use rtens\proto\CommandAction;
use rtens\proto\Event;
use rtens\proto\SingletonAggregateRoot;
use rtens\scrut\Assert;
use watoki\reflect\type\ClassType;
use watoki\reflect\type\StringType;

class IdentifyAggregatesSpec extends Specification {

    function addIdentifierProperty(Assert $assert) {
        eval('namespace proto\test\domain;
        class AddIdentifierProperty extends \\' . AggregateRoot::class . ' {
            function handleFoo() {
                $this->recordThat("This happened", [$this->identifier]);
            }
        }');

        $this->execute('AddIdentifierProperty$Foo', [
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

        $this->execute('Singleton$Foo');

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

        $this->execute('StaticIdentifier$Foo', [
            CommandAction::AGGREGATE_IDENTIFIER_KEY => new $identifierClass('bar')
        ]);

        $assert($this->domin->actions->getAction('StaticIdentifier$Foo')->parameters(), [
            new Parameter(CommandAction::AGGREGATE_IDENTIFIER_KEY, new ClassType($identifierClass), true)
        ]);

        $assert(get_class($this->recordedEvents()[0]->getAggregateIdentifier()),
            $identifierClass);
    }
}