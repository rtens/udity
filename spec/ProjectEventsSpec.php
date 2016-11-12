<?php
namespace rtens\proto;

use rtens\domin\Parameter;
use rtens\proto\domain\command\Singleton;
use rtens\proto\domain\query\DefaultProjection;
use watoki\reflect\type\StringType;

class ProjectEventsSpec extends Specification {

    function doNotRegisterDefaultProjection() {
        $this->define('Foo', DefaultProjection::class);
        $this->runApp();
        $this->assert->not()->contains($this->actionIds(), 'DefaultProjection');
    }

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
            function applyThat($two, $one) {
                $this->applied = $one . $two;
            }
        ');

        $this->recordThat('Bar', 'foo', 'NotThis');
        $this->recordThat('Bar', 'foo', 'That', ['one' => 'And', 'two' => 'This']);

        $result = $this->execute('Bar');
        $this->assert($result->applied, 'AndThis');
    }

    function applyEventsPerAggregate() {
        $this->define('Foo', \stdClass::class);
        $this->define('Bar', \stdClass::class);
        $this->define('Baz', DefaultProjection::class, '
            public $applied = "";
            function forFooApplyThat($one) {
                $this->applied .= "Foo:" . $one;
            }
            function forBarApplyThat($one) {
                $this->applied .= "Bar:" . $one;
            }
        ');

        $this->recordThat('Bar', 'bla', 'That', ['one']);
        $this->recordThat('Foo', 'bla', 'That', ['two']);
        $this->recordThat('NotAClass', 'bla', 'That');

        $result = $this->execute('Baz');
        $this->assert($result->applied, 'Bar:oneFoo:two');
    }

    function injectEvent() {
        $this->define('Bar', DefaultProjection::class, '
            function applyThat(\\' . Event::class . ' $e) {
                $this->applied = $e->getName();
            }
        ');

        $this->recordThat('Bar', 'foo', 'That');

        $result = $this->execute('Bar');
        $this->assert($result->applied, 'That');
    }

    function injectIdentifier() {
        $this->define('Bar', DefaultProjection::class, '
            function applyThat(\\' . AggregateIdentifier::class . ' $id) {
                $this->applied = $id;
            }
        ');

        $this->recordThat('Bar', 'foo', 'That');

        $result = $this->execute('Bar');
        $this->assert($result->applied, $this->id('Bar', 'foo'));
    }

    function presentation() {
        $this->define('Bar', DefaultProjection::class, '
            function applyFooThat() {}
        ');
        $this->runApp();

        $this->assert($this->domin->actions->getAction('Bar')->caption(), 'Show Bar');
        $this->assert(array_keys($this->domin->groups->getActionsOf('Bar')), ['Bar']);
    }

    function passArguments() {
        $this->define('Bar', DefaultProjection::class, '
            function __construct($one, $two = "") {
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
            new Parameter('two', new StringType(), false),
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

    function plaintProjection() {
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
            function __construct($one = "this", $two = "that") {}
            function setFoo($foo) {}
        ');

        $this->runApp();
        $this->assert($this->action('Foo')->fill(['two' => 'other']), [
            'one' => 'this',
            'two' => 'other'
        ]);
    }
}