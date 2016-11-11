<?php
namespace rtens\proto;

use rtens\domin\delivery\web\renderers\link\types\ClassLink;
use rtens\proto\app\ui\actions\AggregateCommandAction;
use rtens\proto\domain\command\Aggregate;
use rtens\proto\domain\objects\DomainObject;
use rtens\proto\domain\query\DefaultProjection;

class LinkActionsSpec extends Specification {

    function linkAggregateCommandToProjection() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {}
        ');
        $projection = $this->define('Bar', DefaultProjection::class, '
            /** @var FooIdentifier */
            public $that;
            function __construct($in) { $this->that = $in; }
        ');

        $links = $this->linksOfProjection('Bar', 'Foo', 'one');

        $this->assert->size($links, 1);
        $this->assert($links[0]->actionId(), 'Foo$Bar');
        $this->assert($links[0]->parameters(new $projection($this->id('Foo', 'one'))), [
            AggregateCommandAction::IDENTIFIER_KEY => ['key' => 'one', 'fix' => true]
        ]);
    }

    function linkQueryToProjection() {
        $this->define('Foo', Aggregate::class);
        $projection = $this->define('Bar', DefaultProjection::class, '
            /** @var FooIdentifier */
            public $that;
            function __construct(FooIdentifier $in) { $this->that = $in; }
        ');

        $links = $this->linksOfProjection('Bar', 'Foo', 'one');

        $this->assert->size($links, 1);

        $this->assert($links[0]->actionId(), 'Bar');
        $this->assert($links[0]->parameters(new $projection($this->id('Foo', 'one'))), [
            'in' => ['key' => 'one', 'fix' => true]
        ]);
    }

    function linkDomainObjectToItself() {
        $projection = $this->define('Foo', DomainObject::class, '
            function doThat() {}
        ');

        $links = $this->linksOfProjection('Foo', 'Foo', 'one');

        $this->assert->size($links, 2);

        $this->assert($links[0]->actionId(), 'Foo');
        $this->assert($links[0]->parameters(new $projection($this->id('Foo', 'one'))), [
            'identifier' => ['key' => 'one', 'fix' => true],
        ]);

        $this->assert($links[1]->actionId(), 'Foo$doThat');
        $this->assert($links[1]->parameters(new $projection($this->id('Foo', 'two'))), [
            AggregateCommandAction::IDENTIFIER_KEY => ['key' => 'two', 'fix' => true],
        ]);
    }

    function linkActionsToIdentifiers() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {}
        ');

        $links = $this->linksOf($this->id('Foo'));

        $this->assert->size($links, 1);

        $this->assert($links[0]->actionId(), 'Foo$Bar');
        $this->assert($links[0]->parameters($this->id('Foo', 'that')), [
            AggregateCommandAction::IDENTIFIER_KEY => ['key' => 'that', 'fix' => true],
        ]);
    }

    function multipleMatchingProperties() {
    }

    function notActuallyAnIdentifier() {
    }

    function wrongSuffix() {
    }

    /**
     * @param string $className
     * @param string $aggregate
     * @param string $key
     * @return ClassLink[]
     */
    private function linksOfProjection($className, $aggregate, $key = null) {
        $projectionClass = $this->fullname($className);
        $object = new $projectionClass($this->id($aggregate, $key));
        return array_values($this->linksOf($object));
    }

    /**
     * @param $object
     * @return ClassLink[]
     */
    private function linksOf($object) {
        $this->runApp();
        return $this->domin->links->getLinks($object);
    }
}