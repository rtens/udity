<?php
namespace spec\rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\Event;
use rtens\proto\Projecting;
use rtens\proto\Projection;
use rtens\proto\SingletonAggregateRoot;
use watoki\reflect\type\StringType;

class ProjectEventsSpec extends Specification  {

    function projectionDoesNotExist() {
        try {
            $this->execute('Foo');
            $this->assert->fail();
        } catch (\Exception $exception) {
            $this->assert->pass();
        }
    }

    function emptyProjection() {
        $this->define('EmptyProjection', Projection::class);

        $result = $this->execute('EmptyProjection');
        $this->assert(is_object($result));
        $this->assert(substr(get_class($result), -strlen('EmptyProjection')), 'EmptyProjection');
    }

    function applyEvents() {
        $this->define('ProjectEvents', Projection::class, '
            function applyThat($two, \rtens\proto\Event $e, $one) {
                $this->applied = $e->getName() . $one . $two;
            }
        ');

        $this->events->append(new Event($this->id('foo'), 'NotThis'), $this->id('foo'));
        $this->events->append(new Event($this->id('foo'), 'That', ['one' => 'And', 'two' => 'This']), $this->id('foo'));

        $result = $this->execute('ProjectEvents');
        $this->assert($result->applied, 'ThatAndThis');
    }

    function passArguments() {
        $this->define('PassArguments', Projection::class, '
            function __construct($one, $two) {
                $this->passed = $one . $two;
            }
        ');

        $result = $this->execute('PassArguments', [
            'two' => 'Bar',
            'one' => 'Foo',
        ]);

        $this->assert($this->domin->actions->getAction('PassArguments')->parameters(), [
            new Parameter('one', new StringType(), true),
            new Parameter('two', new StringType(), true),
        ]);
        $this->assert($result->passed, 'FooBar');
    }

    function aggregateAsProjection() {
        $this->define('AggregateAsProjection', SingletonAggregateRoot::class, '
            function applyThat() {
                $this->applied = true;
            }
        ', Projecting::class);

        $this->events->append(new Event($this->id('foo'), 'That'), $this->id('foo'));

        $result = $this->execute('AggregateAsProjection');
        $this->assert($result->applied, true);
    }
}