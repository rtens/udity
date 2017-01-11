<?php
namespace rtens\udity\check;

use rtens\udity\AggregateIdentifier;
use rtens\udity\domain\command\Aggregate;
use rtens\udity\domain\query\DefaultProjection;
use rtens\udity\Event;
use rtens\udity\Projection;
use rtens\udity\utils\Time;

class CheckProjectionsSpec extends CheckDomainSpecification {

    function notAProjection() {
        $this->shouldFail(function (DomainSpecification $spec) {
            $spec->whenProject('Foo');
        }, 'Class Foo does not exist');
    }

    function emptyProjection() {
        $Foo = $this->define('Foo', DefaultProjection::class);

        $this->shouldFail(function (DomainSpecification $spec) use ($Foo) {
            $spec->whenProject($Foo);
            $spec->projection('Bar');
        }, 'Projection is not an instance of Bar');
    }

    function unexpectedProjection() {
        $Foo = $this->define('Foo', DefaultProjection::class , '
            function foo() {}
        ');

        $this->shouldFail(function (DomainSpecification $spec) use ($Foo) {
            $spec->whenProject($Foo);
            $spec->assert()->equals($spec->projection($this->fqn('Foo'))->foo(), 'foo');
        }, "NULL should equal 'foo'");
    }

    function applyEvents() {
        $Bar = $this->define('Bar', Aggregate::class);
        $Foo = $this->define('Foo', Projection::class, '
            private $count = 0;
            function apply(\\' . Event::class . ' $event) {
                $this->count++;
            }
            function currentCount() {
                return $this->count;
            }
        ');

        $this->shouldPass(function (DomainSpecification $spec) use ($Foo, $Bar) {
            $spec->givenThat('Any', $Bar);
            $spec->givenThat('Other', $Bar);

            $spec->whenProject($Foo);
            $spec->assert()->equals($spec->projection($Foo)->currentCount(), 2);
        });
    }

    function timeEvents() {
        $FooIdentifier = $this->define('FooIdentifier', AggregateIdentifier::class);
        $Foo = $this->define('Foo', Aggregate::class, '
            public $times = [];
            function apply(\\' . Event::class . ' $event) {
                $this->times[] = $event->getWhen();
            }
        ', Projection::class);

        $this->shouldPass(function (DomainSpecification $spec) use ($Foo, $FooIdentifier) {
            $spec->givenThat('First', $Foo)->at('12:00');
            Time::freeze('13:00');
            $spec->givenThat('Second', $Foo);
            Time::freeze('14:00');

            $spec->whenProject($Foo, [new $FooIdentifier('foo')]);
            $spec->assert()->equals($spec->projection($Foo)->times, [
                new \DateTimeImmutable('12:00'),
                new \DateTimeImmutable('13:00')
            ]);
        });
    }

    function constructorParameters() {
        $Foo = $this->define('Foo', DefaultProjection::class, '
            function __construct($one, $two) {
                $this->bar = $one . $two;
            }');

        $this->shouldPass(function (DomainSpecification $spec) use ($Foo) {
            $spec->whenProject($Foo, ['two' => 'Dos', 0 => 'Uno']);
            $spec->assert()->equals($spec->projection($Foo)->bar, 'UnoDos');
        });
    }
}