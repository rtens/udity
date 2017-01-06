<?php
namespace rtens\udity;

use rtens\udity\check\DomainSpecification;
use rtens\udity\domain\command\Aggregate;
use rtens\udity\domain\query\DefaultProjection;

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