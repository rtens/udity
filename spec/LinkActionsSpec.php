<?php
namespace rtens\proto;

use rtens\domin\delivery\web\renderers\link\types\ClassLink;
use rtens\proto\app\ui\AggregateCommandAction;
use rtens\proto\domain\command\Aggregate;
use rtens\proto\domain\command\Singleton;
use rtens\proto\domain\objects\DomainObject;
use rtens\proto\domain\query\DefaultProjection;

class LinkActionsSpec extends Specification {

    public function before() {
        $this->assert->incomplete('tabula rasa');
    }

    function linkAggregateToProjection() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {}
        ');
        $projection = $this->define('Bar', DefaultProjection::class, '
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
            AggregateCommandAction::IDENTIFIER_KEY => 'one'
        ]);
    }

    function linkProjectionToProjection() {
        $this->define('Foo', Aggregate::class);
        $projection = $this->define('Bar', DefaultProjection::class, '
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
            'in' => 'one'
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
            'identifier' => 'one',
        ]);

        $this->assert($links[1]->actionId(), 'Foo$doThat');
        $this->assert($links[1]->parameters($object), [
            AggregateCommandAction::IDENTIFIER_KEY => 'one',
        ]);
    }

    function linkActionsToIdentifiers() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {}
        ');

        $this->runApp();
        $object = $this->id('Foo', 'that');
        $links = $this->links($object);

        $this->assert->size($links, 1);

        $this->assert($links[0]->actionId(), 'Foo$Bar');
        $this->assert($links[0]->parameters($object), [
            AggregateCommandAction::IDENTIFIER_KEY => 'that',
        ]);
    }

    function linkAggregateToSingletons() {
        $projection = $this->define('Foo', Singleton::class, '
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