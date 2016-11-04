<?php
namespace spec\rtens\proto;

use rtens\proto\app\ui\CommandAction;
use rtens\proto\domain\objects\DomainObject;
use rtens\proto\Event;

class InvokeDomainObjectMethodsSpec extends Specification {

    function invalidMethodName() {
        $this->define('Foo', DomainObject::class, '
            function did() {}
        ');

        if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
            $this->define('Bar', DomainObject::class, '
                function do() {}
            ');
        }

        $this->runApp();
        $this->assert->not()->contains($this->actionIds(), 'Foo$do');
        $this->assert->not()->contains($this->actionIds(), 'Bar$do');
    }

    function handleCommand() {
        $this->define('Foo', DomainObject::class, '
            function doBar($baz) {}
        ');

        $this->execute('Foo$doBar', [
            CommandAction::IDENTIFIER_KEY => $this->id('Foo', 'one'),
            'baz' => 'bla',
        ]);

        $this->assert($this->recordedEvents(), [
            new Event($this->id('Foo', 'one'), 'DidBar', [
                'baz' => 'bla'
            ])
        ]);
    }

    function fail() {
        $this->define('Foo', DomainObject::class, '
            function doBar($baz) {
                throw new \Exception("Failed");
            }
        ');

        try {
            $this->execute('Foo$doBar', [
                CommandAction::IDENTIFIER_KEY => $this->id('Foo', 'one'),
                'baz' => 'bla',
            ]);
        } catch (\Exception $exception) {
            $this->assert($exception->getMessage(), "Failed");
            $this->assert($this->recordedEvents(), []);
            return;
        }

        $this->assert->fail();
    }

    function onlyDidMethod() {
        $this->define('Foo', DomainObject::class, '
            function didBar($baz) {}
        ');

        $this->execute('Foo$doBar', [
            CommandAction::IDENTIFIER_KEY => $this->id('Foo', 'one'),
            'baz' => 'bla',
        ]);

        $this->assert($this->recordedEvents(), [
            new Event($this->id('Foo', 'one'), 'DidBar', [
                'baz' => 'bla'
            ])
        ]);
    }

    function doAndDidMethods() {
        $this->define('Foo', DomainObject::class, '
            function doBar($baz) {}
            function didBar($baz) {}
        ');

        $this->runApp();
        $this->assert->pass();
    }

    function applyEvent() {
        $this->define('Foo', DomainObject::class, '
            function didBar($baz) {
                $this->did = $baz;
            }
        ');

        $this->recordThat('Foo', 'one', 'DidBar', ['baz' => 'that']);

        $object = $this->execute('Foo', [
            'identifier' => $this->id('Foo', 'one')
        ]);

        $this->assert($object->did, 'that');
    }
}
