<?php
namespace rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\app\ui\actions\AggregateCommandAction;
use rtens\proto\domain\command\Aggregate;
use rtens\proto\domain\command\Singleton;
use watoki\reflect\type\ClassType;

class IdentifyAggregatesSpec extends Specification {

    function addIdentifierProperty() {
        $class = $this->define('Root', Aggregate::class, '
            function handleFoo() {
                $this->recordThat("This happened", [$this->getIdentifier()]);
            }
        ');

        $this->execute('Root$Foo', [
            AggregateCommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
        ]);

        $this->assert($this->action('Root$Foo')->parameters(), [
            new Parameter(AggregateCommandAction::IDENTIFIER_KEY, new ClassType($class . 'Identifier'), true)
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
        $this->define('Root', Singleton::class, '
            function handleFoo() {
                $this->recordThat("This happened", [$this->getIdentifier()]);
            }
        ');

        $this->execute('Root$Foo', [
            AggregateCommandAction::IDENTIFIER_KEY => $this->id('Root', 'baz')
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
        $this->define('Root', Aggregate::class, '
            function handleFoo() {
                $this->recordThat("This happened", [$this->getIdentifier()]);
            }
        ');
        $identifierClass = $this->define('RootIdentifier', AggregateIdentifier::class);

        $this->execute('Root$Foo', [
            AggregateCommandAction::IDENTIFIER_KEY => new $identifierClass('bar')
        ]);

        $this->assert($this->action('Root$Foo')->parameters(), [
            new Parameter(AggregateCommandAction::IDENTIFIER_KEY, new ClassType($identifierClass), true)
        ]);

        $this->assert(get_class($this->recordedEvents()[0]->getAggregateIdentifier()),
            $identifierClass);
    }

    function classAlreadyDeclared() {
        $this->define('FooIdentifier', AggregateIdentifier::class);
        $this->domainClasses = [];
        $this->define('Foo', Aggregate::class);

        $this->runApp();

        $this->assert->pass();
    }
}