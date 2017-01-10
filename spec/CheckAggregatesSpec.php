<?php
namespace rtens\udity;

use rtens\udity\check\DomainSpecification;
use rtens\udity\check\event\Events;
use rtens\udity\domain\command\Aggregate;

class CheckAggregatesSpec extends CheckDomainSpecification {

    function notACommand() {
        $this->define('Foo', Aggregate::class);

        $this->shouldFail(function (DomainSpecification $specification) {
            $specification->when($this->fqn('Foo'))->handleBar();
        }, 'Action [Foo$Bar] is not registered.');
    }

    function noEvents() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {}
        ');

        $this->shouldPass(function (DomainSpecification $spec) {
            $spec->when($this->fqn('Foo'))->handleBar();
            $spec->then(Events::any())->shouldCount(0);
        });
    }

    function passParameters() {
        $this->define('Foo', Aggregate::class, '
            function handleBar($in) {
                $this->recordThat($in);
            }
        ');

        $this->shouldPass(function (DomainSpecification $spec) {
            $spec->when($this->fqn('Foo'), 'foo')->handleBar('foo');
            $spec->then(Events::named('foo'))->shouldBeAppended();
        });
    }

    function applyEventsFromContext() {
        $this->define('Foo', Aggregate::class, '
            private $said;
            function handleBar() {
                $this->recordThat("Bared", ["said" => $this->said]);
            }
            function applyBared($said) {
                $this->said .= $said;
            }
        ');

        $this->shouldPass(function (DomainSpecification $spec) {
            $spec->givenThat('Bared', $this->fqn('Foo'))->with('said', 'Zero');
            $spec->givenThat('Bared', $this->fqn('Foo'), 'foo')->with('said', 'One');
            $spec->givenThat('Bared', $this->fqn('Foo'), 'bar')->with('said', 'Two');
            $spec->givenThat('Bared', $this->fqn('Foo'), 'foo')->with('said', 'Three');

            $spec->when($this->fqn('Foo'), 'foo')->handleBar();
            $spec->then(Events::named('Bared')->with('said', 'OneThree'))->shouldBeAppended();
            $spec->then(Events::any())->shouldCount(1);
        });
    }
}