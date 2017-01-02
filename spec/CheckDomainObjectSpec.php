<?php
namespace rtens\udity;

use rtens\scrut\failures\IncompleteTestFailure;
use rtens\udity\check\DomainSpecification;
use rtens\udity\check\event\Events;
use rtens\udity\domain\objects\DomainObject;

class CheckDomainObjectSpec extends CheckDomainSpecification {

    function notACommand() {
        $this->define('Foo', DomainObject::class);

        $this->shouldFail(function (DomainSpecification $spec) {
            $spec->when($this->fqn('Foo'))->doBar();
        }, 'Action [Foo$doBar] is not registered.');
    }

    function eventHandlerIsNotACommand() {
        $this->define('Foo', DomainObject::class, '
            function didBar() {}');

        $this->shouldFail(function (DomainSpecification $spec) {
            $spec->when($this->fqn('Foo'))->didBar();
        }, 'Action [Foo$didBar] is not registered.');
    }

    function recordEvent() {
        $this->define('Foo', DomainObject::class, '
            function doBar() {}
        ');

        $this->shouldPass(function (DomainSpecification $spec) {
            $spec->when($this->fqn('Foo'))->doBar();
            $spec->then(Events::named('DidBar'))->shouldCount(1);
        });
    }

    function eventHandlerInfersACommand() {
        $this->define('Foo', DomainObject::class, '
            function didBar() {}');

        $this->shouldPass(function (DomainSpecification $spec) {
            $spec->when($this->fqn('Foo'))->doBar();
            $spec->then(Events::named('DidBar'))->shouldCount(1);
        });
    }

    function applyEventsFromContext() {
        $this->define('Foo', DomainObject::class, '
            private $that;
            function didBar($that) {
                $this->that .= $that;
            }
            function doCheck() {
                if ($this->that != "OneThree") {
                    throw new \Exception();
                }
            }
        ');

        $this->shouldPass(function (DomainSpecification $a) {
            $a->given($this->fqn('Foo'))->didBar('Zero');
            $a->given($this->fqn('Foo'), 'foo')->didBar('One');
            $a->given($this->fqn('Foo'), 'bar')->didBar('Two');
            $a->given($this->fqn('Foo'), 'foo')->didBar('Three');

            $a->when($this->fqn('Foo'), 'foo')->doCheck();
        });
    }

    function optionalParameters() {
        $this->define('Foo', DomainObject::class, '
            function didBar($one, $two = "optional") {
                $this->optional = $two;
            }
            function doCheck() {
                if ($this->optional != "optional") {
                    throw new \Exception();
                }
            }
        ');

        $this->shouldPass(function (DomainSpecification $a) {
            $a->given($this->fqn('Foo'))->didBar('uno');
            $a->when($this->fqn('Foo'))->doCheck();
        });
    }

    function projectObject() {
        throw new IncompleteTestFailure('TBD');

        $this->define('Foo', DomainObject::class);

        $this->shouldPass(function (DomainSpecification $a) {
            $a->whenProject($this->fqn('Foo'), 'bar');
            $a->thenProjected('')->getIdentifier()->shouldEqual('bar');
        });
    }
}