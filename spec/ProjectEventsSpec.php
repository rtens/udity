<?php
namespace rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\domain\command\Singleton;
use rtens\proto\domain\query\DefaultProjection;
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
        $this->define('Bar', DefaultProjection::class);

        $result = $this->execute('Bar');
        $this->assert(is_object($result));
        $this->assert(substr(get_class($result), -strlen('Bar')), 'Bar');
    }

    function applyEvents() {
        $this->define('Bar', DefaultProjection::class, '
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
        $this->define('Bar', DefaultProjection::class, '
            function __construct($one, $two) {
                $this->passed = $one . $two;
            }
            function setFoo($foo) {}
        ');

        $result = $this->execute('Bar', [
            'two' => 'Bar',
            'one' => 'Foo',
        ]);

        $this->assert($this->action('Bar')->parameters(), [
            new Parameter('one', new StringType(), true),
            new Parameter('two', new StringType(), true),
        ]);
        $this->assert($result->passed, 'FooBar');
    }

    function aggregateAsProjection() {
        $this->define('Bar', Singleton::class, '
            function applyThat() {
                $this->applied = true;
            }
        ', Projection::class);

        $this->recordThat('Bar', 'asd', 'That');

        $result = $this->execute('Bar');
        $this->assert($result->applied, true);
    }

    function projectingImplementation() {
        $this->define('Foo', \stdClass::class, '
            function apply(\\' . Event::class . ' $e) {
                $this->applied = true;
            }
        ', Projection::class);

        $this->recordThat('Foo', 'asd', 'That');

        $result = $this->execute('Foo');
        $this->assert($this->action('Foo')->parameters(), []);
        $this->assert($this->action('Foo')->fill([]), []);

        $this->assert($result->applied, true);
    }

    function fillDefaultValues() {
        $this->define('Foo', DefaultProjection::class, '
            function __construct($one = "that one") {}
            function setFoo($foo) {}
        ');

        $this->runApp();
        $this->assert($this->action('Foo')->fill([]), [
            'one' => 'that one'
        ]);
    }
}