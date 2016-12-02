<?php
namespace rtens\proto;

use rtens\udity\check\DomainSpecification;
use rtens\udity\CheckDomainSpecification;
use rtens\udity\domain\command\Aggregate;
use rtens\udity\domain\query\DefaultProjection;
use rtens\udity\Event;
use rtens\udity\Projection;

class CheckProjectionsSpec extends CheckDomainSpecification {

    function notAProjection() {
        $this->shouldFail(function (DomainSpecification $spec) {
            $spec->whenProject($this->fqn('Foo'));
        }, 'Class ' . $this->fqn('Foo') . ' does not exist');
    }

    function emptyProjection() {
        $this->define('Foo', DefaultProjection::class);

        $this->shouldFail(function (DomainSpecification $spec) {
            $spec->whenProject($this->fqn('Foo'));
            $spec->thenProjected($this->fqn('Bar'));
        }, 'Projection is not an instance of ' . $this->fqn('Bar'));
    }

    function unexpectedProjection() {
        $this->define('Foo', DefaultProjection::class , '
            function foo() {}
        ');

        $this->shouldFail(function (DomainSpecification $spec) {
            $spec->whenProject($this->fqn('Foo'));
            $spec->thenProjected($this->fqn('Foo'))->foo()->shouldEqual('foo');
        }, 'Expected [foo] but got []');
    }

    function applyEvents() {
        $this->define('Bar', Aggregate::class);
        $this->define('Foo', Projection::class, '
            private $count = 0;
            function apply(\\' . Event::class . ' $event) {
                $this->count++;
            }
            function currentCount() {
                return $this->count;
            }
        ');

        $this->shouldPass(function (DomainSpecification $spec) {
            $spec->given('Any', $this->fqn('Bar'));
            $spec->given('Other', $this->fqn('Bar'));

            $spec->whenProject($this->fqn('Foo'));
            $spec->thenProjected($this->fqn('Foo'))->currentCount()->shouldEqual(2);
        });
    }
}