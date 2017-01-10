<?php
namespace rtens\udity;

use rtens\udity\check\DomainSpecification;
use rtens\udity\check\event\Events;
use rtens\udity\domain\objects\DomainObject;

class CheckDomainObjectSpec extends CheckDomainSpecification {

    function notACommand() {
        $Foo = $this->define('Foo', DomainObject::class);

        $this->shouldFail(function (DomainSpecification $spec) use ($Foo) {
            $spec->when($Foo)->doBar();
        }, 'Action [Foo$doBar] is not registered.');
    }

    function eventHandlerIsNotACommand() {
        $Foo = $this->define('Foo', DomainObject::class, '
            function didBar() {}');

        $this->shouldFail(function (DomainSpecification $spec) use ($Foo) {
            $spec->when($Foo)->didBar();
        }, 'Action [Foo$didBar] is not registered.');
    }

    function recordEvent() {
        $Foo = $this->define('Foo', DomainObject::class, '
            function doBar() {}
        ');

        $this->shouldPass(function (DomainSpecification $spec) use ($Foo) {
            $spec->when($Foo)->doBar();
            $spec->then(Events::named('DidBar'))->shouldCount(1);
        });
    }

    function eventHandlerInfersACommand() {
        $Foo = $this->define('Foo', DomainObject::class, '
            function didBar() {}');

        $this->shouldPass(function (DomainSpecification $spec) use ($Foo) {
            $spec->when($Foo)->doBar();
            $spec->then(Events::named('DidBar'))->shouldCount(1);
        });
    }

    function applyEventsFromContext() {
        $Foo = $this->define('Foo', DomainObject::class, '
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

        $this->shouldPass(function (DomainSpecification $spec) use ($Foo) {
            $spec->given($Foo)->didBar('Zero');
            $spec->given($Foo, 'foo')->didBar('One');
            $spec->given($Foo, 'bar')->didBar('Two');
            $spec->given($Foo, 'foo')->doBar('Three');

            $spec->when($Foo, 'foo')->doCheck();
        });
    }

    function optionalParameters() {
        $Foo = $this->define('Foo', DomainObject::class, '
            function didBar($one, $two = "optional") {
                $this->optional = $two;
            }
            function doCheck() {
                if ($this->optional != "optional") {
                    throw new \Exception();
                }
            }
        ');

        $this->shouldPass(function (DomainSpecification $spec) use ($Foo) {
            $spec->given($Foo)->didBar('uno');
            $spec->when($Foo)->doCheck();
        });
    }

    function projectObject() {
        $Foo = $this->define('Foo', DomainObject::class);

        $this->shouldPass(function (DomainSpecification $spec) use ($Foo) {
            $spec->whenProjectObject($Foo, 'bar');
            $spec->assert()->equals($spec->projection($Foo)->getIdentifier()->getKey(), 'bar');
        });
    }

    function createObject() {
        $Foo = $this->define('Foo', DomainObject::class, '
            function created() {}');

        $this->shouldPass(function (DomainSpecification $spec) use ($Foo) {
            $spec->when($Foo, 'foo')->created('bar');
            $spec->then(Events::named('Created'))->shouldCount(1);
        });
    }

    function createdObject() {
        $Foo = $this->define('Foo', DomainObject::class, '
            function created($one) {
                $this->one = $one;
            }');

        $this->shouldPass(function (DomainSpecification $spec) use ($Foo) {
            $spec->given($Foo)->created('bar');
            $spec->whenProjectObject($Foo);
            $spec->assert()->equals($spec->projection($Foo)->one, 'bar');
        });
    }
}