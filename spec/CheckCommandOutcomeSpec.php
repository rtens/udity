<?php
namespace rtens\udity;

use rtens\udity\check\DomainSpecification;
use rtens\udity\check\event\Events;
use rtens\udity\domain\command\Aggregate;

class CheckCommandOutcomeSpec extends CheckDomainSpecification {

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

        $this->shouldPass(function (DomainSpecification $spec) {
            $spec->when($this->fqn('Foo'))->handleBar();
            $spec->then(Events::named('one')->with('foo', 'bar'))->shouldCount(1);
            $spec->then(Events::any()->with('foo', 'bar'))->shouldCount(2);
            $spec->then(Events::any()->with('foo', 'foo'))->shouldNotBeAppended();
            $spec->then(Events::any()->with('bar', 'bar'))->shouldNotBeAppended();
        });
    }

    function matchIdentifier() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {
                $this->recordThat("bared");          
            }
        ');

        $this->shouldPass(function (DomainSpecification $spec) {
            $spec->when($this->fqn('Foo'), 'one')->handleBar();

            $spec->then(Events::named('bared'))->shouldCount(1);
            $spec->then(Events::named('bared')->in('one'))->shouldCount(1);
            $spec->then(Events::named('bared')->in('not one'))->shouldCount(0);
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