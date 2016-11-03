<?php
namespace spec\rtens\proto;

use rtens\domin\delivery\web\renderers\link\types\ClassLink;
use rtens\proto\AggregateRoot;
use rtens\proto\CommandAction;
use rtens\proto\DomainObject;
use rtens\proto\Projection;
use rtens\proto\SingletonAggregateRoot;

class LinkActionsSpec extends Specification {

    function linkAggregateToProjection() {
        $this->define('Foo', AggregateRoot::class, '
            function handleBar() {}
        ');
        $projection = $this->define('Bar', Projection::class, '
            /** @var FooIdentifier */
            public $that;
            function __construct($in) { $this->that = $in; }
        ');

        $this->runApp();
        $object = new $projection($this->id('Foo', 'one'));
        $links = $this->links($object);

        $this->assert->size($links, 1);

        $this->assert($links[0]->actionId(), 'Foo$Bar');
        $this->assert($links[0]->parameters($object), [
            CommandAction::IDENTIFIER_KEY => ['key' => 'one']
        ]);
    }

    function linkProjectionToProjection() {
        $this->define('Foo', AggregateRoot::class);
        $projection = $this->define('Bar', Projection::class, '
            /** @var FooIdentifier */
            public $that;
            function __construct(FooIdentifier $in) { $this->that = $in; }
        ');

        $this->runApp();
        $object = new $projection($this->id('Foo', 'one'));
        $links = $this->links($object);

        $this->assert->size($links, 1);

        $this->assert($links[0]->actionId(), 'Bar');
        $this->assert($links[0]->parameters($object), [
            'in' => ['key' => 'one']
        ]);
    }

    function linkDomainObjectToItself() {
        $projection = $this->define('Foo', DomainObject::class, '
            function doThat() {}
        ');

        $this->runApp();
        $object = new $projection($this->id('Foo', 'one'));
        $links = $this->links($object);

        $this->assert->size($links, 2);

        $this->assert($links[0]->actionId(), 'Foo');
        $this->assert($links[0]->parameters($object), [
            'identifier' => ['key' => 'one'],
        ]);

        $this->assert($links[1]->actionId(), 'Foo$doThat');
        $this->assert($links[1]->parameters($object), [
            CommandAction::IDENTIFIER_KEY => ['key' => 'one'],
        ]);
    }

    function linkActionsToIdentifiers() {
        $this->define('Foo', AggregateRoot::class, '
            function handleBar() {}
        ');

        $this->runApp();
        $object = $this->id('Foo', 'that');
        $links = $this->links($object);

        $this->assert->size($links, 1);

        $this->assert($links[0]->actionId(), 'Foo$Bar');
        $this->assert($links[0]->parameters($object), [
            CommandAction::IDENTIFIER_KEY => ['key' => 'that'],
        ]);
    }

    function linkAggregateToSingletons() {
        $projection = $this->define('Foo', SingletonAggregateRoot::class, '
            function handleBar() {}
        ');

        $this->runApp();
        $object = new $projection($this->id('Foo'));
        $links = $this->links($object);

        $this->assert->size($links, 1);

        $this->assert($links[0]->actionId(), 'Foo$Bar');
        $this->assert($links[0]->parameters($object), []);
    }

    /**
     * @param object $object
     * @return ClassLink[]
     */
    private function links($object) {
        return $this->domin->links->getLinks($object);
    }
}