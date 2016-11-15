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

    function unexpectedEvent() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {
                $this->recordThat("Bared");
            }
        ');

        $this->shouldFail(function (DomainSpecification $spec) {
            $spec->when($this->fqn('Foo'))->handleBar();
            $spec->then(Events::any())->shouldCount(0);
        }, 'Expected to match 0 events but got 1');
    }

    function matchEvents() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {
                $this->recordThat("One");
                $this->recordThat("Two");
                $this->recordThat("Two");
            }
        ');

        $this->shouldPass(function (DomainSpecification $spec) {
            $spec->when($this->fqn('Foo'))->handleBar();
            $spec->then(Events::any())->shouldCount(3);
            $spec->then(Events::named('One'))->shouldCount(1);
            $spec->then(Events::named('Two'))->shouldCount(2);
            $spec->then(Events::named('One'))->shouldBeAppended();
            $spec->then(Events::named('Two'))->shouldBeAppended();
            $spec->then(Events::named('Three'))->shouldNotBeAppended();
        });
    }

    function matchPayload() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {
                $this->recordThat("one", ["foo" => "bar"]);            
                $this->recordThat("two", ["foo" => "bar"]);            
            }
        ');

        $this->shouldPass(function (DomainSpecification $a) {
            $a->when($this->fqn('Foo'))->handleBar();
            $a->then(Events::named('one')->with('foo', 'bar'))->shouldCount(1);
            $a->then(Events::any()->with('foo', 'bar'))->shouldCount(2);
            $a->then(Events::any()->with('foo', 'foo'))->shouldNotBeAppended();
            $a->then(Events::any()->with('bar', 'bar'))->shouldNotBeAppended();
        });
    }

    function unexpectedPayload() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {
                $this->recordThat("one");            
            }
        ');

        $this->shouldFail(function (DomainSpecification $a) {
            $a->when($this->fqn('Foo'))->handleBar();
            $a->then(Events::any()->with('foo', 'bar'))->shouldBeAppended();
        }, 'Event was not appended');
    }

    function withContext() {
        $this->define('Foo', Aggregate::class, '
            private $said;
            function handleBar() {
                $this->recordThat("Bared", ["said" => $this->said]);
            }
            function applyBared($said) {
                $this->said .= $said;
            }
        ');

        $this->shouldPass(function (DomainSpecification $a) {
            $a->given('Bared')->with('said', 'One');
            $a->given('Bared')->with('said', 'Two');

            $a->when($this->fqn('Foo'))->handleBar();
            $a->then(Events::named('Bared')->with('said', 'OneTwo'))->shouldBeAppended();
            $a->then(Events::any())->shouldCount(1);
        });
    }

    function expectFailure() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {
                throw new \Exception("Nope");
            }
            function handleBaz() {
            }
        ');

        $this->shouldPass(function (DomainSpecification $a) {
            $a->tryTo($this->fqn('Foo'))->handleBar();
            $a->thenShouldFailWith('Nope');
        });

        $this->shouldFail(function (DomainSpecification $a) {
            $a->tryTo($this->fqn('Foo'))->handleBar();
            $a->thenShouldFailWith('Other');
        }, "Failed with 'Nope' instead of 'Other'");

        $this->shouldFail(function (DomainSpecification $a) {
            $a->tryTo($this->fqn('Foo'))->handleBaz();
            $a->thenShouldFailWith('Nope');
        }, 'Did not fail');
    }
}