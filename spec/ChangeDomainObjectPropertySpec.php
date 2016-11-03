<?php
namespace spec\rtens\proto;

use rtens\proto\CommandAction;
use rtens\proto\DomainObject;
use rtens\proto\Event;

class ChangeDomainObjectPropertySpec extends Specification {

    function invalidSetter() {
        $this->define('Foo', DomainObject::class, '
            function setBar($baz, $bez) {}
            function set($baz) {}
        ');

        $this->runApp();

        $this->assert->not()->contains($this->actionIds(), 'Foo$changeBar');
        $this->assert->not()->contains($this->actionIds(), 'Foo$change');
    }

    function changeProperty() {
        $this->define('Foo', DomainObject::class, '
            function setBar($baz) {}
        ');

        $this->execute('Foo$changeBar', [
            CommandAction::IDENTIFIER_KEY => $this->id('Foo', 'one'),
            'baz' => 'new foo'
        ]);

        $this->assert($this->recordedEvents(), [
            new Event($this->id('Foo', 'one'), 'ChangedBar', [
                'baz' => 'new foo'
            ])
        ]);
    }

    function changedProperty() {
        $this->define('Foo', DomainObject::class, '
            function setBar($baz) {
                $this->bar = $baz;
            }
        ');

        $this->recordThat('Foo', 'one', 'ChangedBar', [
            'baz' => 'new bar'
        ]);

        $object = $this->execute('Foo', [
            'identifier' => $this->id('Foo', 'one')
        ]);
        $this->assert($object->bar, 'new bar');
    }

    function preFillProperty() {
        $this->define('Foo', DomainObject::class, '
            function setBar($baz) {
                $this->bar = $baz;
            }
            function getBar() {
                return $this->bar;
            }
        ');

        $this->recordThat('Foo', 'one', 'ChangedBar', [
            'baz' => 'new bar'
        ]);

        $this->runApp();
        $filled = $this->action('Foo$changeBar')->fill([
            CommandAction::IDENTIFIER_KEY => $this->id('Foo', 'one')
        ]);

        $this->assert($filled, [
            CommandAction::IDENTIFIER_KEY => $this->id('Foo', 'one'),
            'baz' => 'new bar'
        ]);
    }

    function notExistingChangedProperty() {
        $this->define('Foo', DomainObject::class);

        $this->recordThat('Foo', 'one', 'ChangedBar', ['baz' => 'yeah']);

        $this->execute('Foo', [
            'identifier' => $this->id('Foo', 'one')
        ]);
        $this->assert->pass();
    }
}