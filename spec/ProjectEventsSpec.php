<?php
namespace spec\rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\Event;
use rtens\proto\Projecting;
use rtens\proto\Projection;
use rtens\proto\SingletonAggregateRoot;
use watoki\reflect\type\StringType;

class ProjectEventsSpec extends Specification {

    function projectionDoesNotExist() {
        try {
            $this->execute('Foo');
        } catch (\Exception $exception) {
            $this->assert->pass();
            return;
        }
        $this->assert->fail();
    }

    function emptyProjection() {
        $this->define('Bar', Projection::class);

        $result = $this->execute('Bar');
        $this->assert(is_object($result));
        $this->assert(substr(get_class($result), -strlen('Bar')), 'Bar');
    }

    function applyEvents() {
        $this->define('Bar', Projection::class, '
            function applyThat($two, \rtens\proto\Event $e, $one) {
                $this->applied = $e->getName() . $one . $two;
            }
        ');

        $this->recordThat('Bar', 'foo', 'NotThis');
        $this->recordThat('Bar', 'foo', 'That', ['one' => 'And', 'two' => 'This']);

        $result = $this->execute('Bar');
        $this->assert($result->applied, 'ThatAndThis');
    }

    function passArguments() {
        $this->define('Bar', Projection::class, '
            function __construct($one, $two) {
                $this->passed = $one . $two;
            }
            function setFoo($foo) {}
        ');

        $result = $this->execute('Bar', [
            'two' => 'Bar',
            'one' => 'Foo',
        ]);

        $this->assert($this->domin->actions->getAction('Bar')->parameters(), [
            new Parameter('one', new StringType(), true),
            new Parameter('two', new StringType(), true),
        ]);
        $this->assert($result->passed, 'FooBar');
    }

    function aggregateAsProjection() {
        $this->define('Bar', SingletonAggregateRoot::class, '
            function applyThat() {
                $this->applied = true;
            }
        ', Projecting::class);

        $this->recordThat('Bar', 'asd', 'That');

        $result = $this->execute('Bar');
        $this->assert($result->applied, true);
    }

    function projectingImplementation() {
        $this->define('Foo', \stdClass::class, '
            function apply(\\' . Event::class . ' $e) {
                $this->applied = true;
            }
        ', Projecting::class);

        $this->recordThat('Foo', 'asd', 'That');

        $result = $this->execute('Foo');
        $this->assert($this->domin->actions->getAction('Foo')->parameters(), []);
        $this->assert($this->domin->actions->getAction('Foo')->fill([]), []);

        $this->assert($result->applied, true);
    }

    function fillDefaultValues() {
        $this->define('Foo', Projection::class, '
            function __construct($one = "that one") {}
            function setFoo($foo) {}
        ');

        $this->runApp();
        $this->assert($this->domin->actions->getAction('Foo')->fill([]), [
            'one' => 'that one'
        ]);
    }
}