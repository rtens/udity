<?php
namespace spec\rtens\proto;

use rtens\proto\CommandAction;
use rtens\proto\DomainObject;
use rtens\proto\Event;

class ChangeDomainObjectPropertySpec extends Specification {

    function invalidSetter() {
        $this->define('Foo', DomainObject::class, '
            function setBar($baz, $bez) {}
        ');

        $this->runApp();
        $this->assert->not()->contains(
            array_keys($this->domin->actions->getAllActions()),
            'Foo$changeBar');
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
            new Event($this->id('Foo', 'one'), 'Changed', [
                'property' => 'Bar',
                'value' => 'new foo'
            ])
        ]);
    }

    function changedProperty() {
        $this->define('Foo', DomainObject::class, '
            function setBar($baz) {
                $this->bar = $baz;
            }
        ');

        $this->recordThat('Foo', 'one', 'Changed', [
            'property' => 'Bar',
            'value' => 'new bar'
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

        $this->recordThat('Foo', 'one', 'Changed', [
            'property' => 'Bar',
            'value' => 'new bar'
        ]);

        $this->runApp();
        $filled = $this->domin->actions->getAction('Foo$changeBar')->fill([
            CommandAction::IDENTIFIER_KEY => $this->id('Foo', 'one')
        ]);

        $this->assert($filled, [
            CommandAction::IDENTIFIER_KEY => $this->id('Foo', 'one'),
            'baz' => 'new bar'
        ]);
    }
}