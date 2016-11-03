<?php
namespace spec\rtens\proto;

use rtens\proto\AggregateRoot;

class LinkCommandsSpec extends Specification {

    function test() {
        $this->define('Foo', AggregateRoot::class, '
            function handleBar() {}
        ');
        $object = $this->define('Bar', \stdClass::class, '
            /** @var FooIdentifier */
            public $that;
        ');

        $this->runApp();

        $this->assert($this->domin->links->getLinks($object), 'that');
    }
}