<?php
namespace rtens\udity;

use rtens\domin\delivery\web\renderers\link\types\ClassLink;
use rtens\udity\app\ui\actions\AggregateCommandAction;
use rtens\udity\domain\command\Aggregate;
use rtens\udity\domain\objects\DomainObject;
use rtens\udity\domain\query\DefaultProjection;

class LinkActionsSpec extends Specification {

    function notActuallyAnIdentifier() {
        $this->define('FooIdentifier', \stdClass::class);
        $this->define('Foo', Aggregate::class, '
            function handleBar() {}
        ');
        $this->define('Bar', DefaultProjection::class, '
            /** @var FooIdentifier */
            public $that;
        ');

        $links = $this->linksOfProjection('Bar');

        $this->assert->size($links, 0);
    }

    function linkAggregateCommandToProjection() {
        $this->define('Foo', Aggregate::class, '
            function handleBar() {}
        ');
        $projection = $this->define('Bar', DefaultProjection::class, '
            /** @var FooIdentifier */
            public $identifier;
            function __construct($in) { $this->identifier = $in; }
        ');

        $links = $this->linksOfAggregate('Bar', 'Foo', 'one');

        $this->assert->size($links, 1);
        $this->assert($links[0]->actionId(), 'Foo$Bar');
        $this->assert($links[0]->parameters(new $projection($this->id('Foo', 'one'))), [
            AggregateCommandAction::IDENTIFIER_KEY => ['key' => 'one', 'fix' => true]
        ]);
        $this->assert($this->linkAction($links[0])->caption(), 'Bar');
    }

    function linkQueryToProjection() {
        $this->define('FooIdentifier', AggregateIdentifier::class);
        $projection = $this->define('Bar', DefaultProjection::class, '
            /** @var FooIdentifier */
            public $identifier;
            function __construct(FooIdentifier $identifier) { $this->identifier = $identifier; }
        ');

        $links = $this->linksOfAggregate('Bar', 'Foo', 'one');

        $this->assert->size($links, 1);

        $this->assert($links[0]->actionId(), 'Bar');
        $this->assert($links[0]->parameters(new $projection($this->id('Foo', 'one'))), [
            'identifier' => ['key' => 'one', 'fix' => true]
        ]);
    }

    function linkDomainObjectToItself() {
        $projection = $this->define('Foo', DomainObject::class, '
            function doThat() {}
        ');

        $links = $this->linksOfAggregate('Foo', 'Foo', 'one');

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

    function multipleProperties() {
        $this->define('Foo', Aggregate::class, '
            function handleFoo() {}        
        ');
        $this->define('Bar', Aggregate::class, '
            function handleBar() {}        
        ');
        $this->define('Baz', DefaultProjection::class, '
            /** @var FooIdentifier */
            public $a;
            /** @var BarIdentifier */
            public $b;
        ');

        $links = $this->linksOfProjection('Baz');

        $this->assert->size($links, 2);

        $this->assert($this->linkAction($links[0])->caption(), 'Foo(a)');
        $this->assert($this->linkAction($links[1])->caption(), 'Bar(b)');
    }

    function multipleActionsPerProperties() {
        $this->define('MyIdentifier', AggregateIdentifier::class);
        $this->define('YourIdentifier', AggregateIdentifier::class);

        $this->define('Foo', Aggregate::class, '
            function handleFoo(
                MyIdentifier $one, 
                YourIdentifier $two, 
                MyIdentifier $three
            ) {}        
        ');
        $this->define('Bar', DefaultProjection::class, '
            /** @var MyIdentifier */
            public $a;
            /** @var YourIdentifier */
            public $b;
            /** @var MyIdentifier */
            public $c;
        ');

        $links = $this->linksOfProjection('Bar');

        $this->assert->size($links, 5);

        $this->assert($this->linkAction($links[0])->caption(), 'Foo(a->one)');
        $this->assert($this->linkAction($links[1])->caption(), 'Foo(c->one)');
        $this->assert($this->linkAction($links[2])->caption(), 'Foo(b->two)');
        $this->assert($this->linkAction($links[3])->caption(), 'Foo(a->three)');
        $this->assert($this->linkAction($links[4])->caption(), 'Foo(c->three)');
    }

    /**
     * @param string $className
     * @param string $aggregate
     * @param string $key
     * @return ClassLink[]
     */
    private function linksOfAggregate($className, $aggregate, $key = null) {
        $projectionClass = $this->fqn($className);
        return array_values($this->linksOf(new $projectionClass($this->id($aggregate, $key))));
    }

    /**
     * @param string $className
     * @return ClassLink[]
     */
    private function linksOfProjection($className) {
        $projectionClass = $this->fqn($className);
        return array_values($this->linksOf(new $projectionClass()));
    }

    /**
     * @param $object
     * @return ClassLink[]
     */
    private function linksOf($object) {
        $this->runApp();
        return $this->domin->links->getLinks($object);
    }

    private function linkAction(ClassLink $link) {
        return $this->domin->actions->getAction($link->actionId());
    }
}