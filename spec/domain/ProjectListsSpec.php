<?php
namespace spec\rtens\udity\domain;

use rtens\udity\domain\objects\DomainObject;
use rtens\udity\domain\objects\DomainObjectList;
use rtens\udity\domain\query\DefaultProjection;
use rtens\udity\domain\query\ProjectionList;
use rtens\udity\Event;
use rtens\udity\Specification;

class ProjectListsSpec extends Specification {

    function projectItems() {
        $Foo = $this->define('Foo', DefaultProjection::class, '
            function applyBared($one) {
                $this->applied[] = $one . " bared";
            }
            function applyBazzed($one) {
                $this->applied[] = $one . " bazzed";
            }
        ');
        $this->define('Bar', ProjectionList::class, '
            protected function matchesEvent(\\' . Event::class . ' $event) {
                return $event->getPayload()["one"] == "uno";
            }
            protected function createItem(\\' . Event::class . ' $event) { 
                return new \\' . $Foo . ';
            }
        ');

        $this->recordThat('Baz', 'baz', 'Bared', ['one' => 'uno']);
        $this->recordThat('Baz', 'baz', 'Bared', ['one' => 'not uno']);
        $this->recordThat('Baz', 'baz', 'Bazzed', ['one' => 'uno']);

        /** @var ProjectionList $list */
        $list = $this->execute('Bar');

        $this->assert($list->getList()[0]->applied, ['uno bared', 'uno bazzed']);
    }

    function projectOtherEvents() {
        $this->define('Bar', \stdClass::class);
        $this->define('Foo', ProjectionList::class, '
            protected function matchesEvent(\\' . Event::class . ' $event) {
                return true;
            }
            protected function createItem(\\' . Event::class . ' $event) { 
                return new \\' . DefaultProjection::class . ';
            }
            
            public function applyFoo($one)       { $this->applied[] = $one . " foo"; }
            public function forBarApplyFoo($one) { $this->applied[] = $one . " foo bar"; }
            public function forBarApplyBar($one) { $this->applied[] = $one . " bar bar"; }
            public function forFooApplyBar($one) { $this->applied[] = $one . " bar foo"; }
        ');

        $this->recordThat('Bar', 'bar', 'Foo', ['one' => 'uno']);

        $list = $this->execute('Foo');

        $this->assert($list->applied, ['uno foo', 'uno foo bar']);
    }

    function listOfDomainObjects() {
        $this->define('Object', DomainObject::class, '
            function created($name) {
                $this->name = $name;
            }
        ');

        $this->recordThat('Object', 'one', 'Created', ['name' => 'One']);
        $this->recordThat('Object', 'two', 'Created', ['name' => 'Two']);
        $this->recordThat('Object', 'three', 'Created', ['name' => 'Three']);

        $this->recordThat('Wrong', 'three', 'Created');

        /** @var DomainObjectList $objects */
        $objects = $this->execute('ObjectList');

        $this->assert(count($objects->getList()), 3);
        $this->assert(is_object($objects->getList()[0]));
        $this->assert($objects->getList()[0]->name, 'One');
        $this->assert($objects->getList()[1]->name, 'Two');
        $this->assert($objects->getList()[2]->name, 'Three');
    }

    function ignoreListClasses() {
        $this->define('Foo', DomainObject::class);
        $this->define('FooList', DomainObjectList::class);

        $this->runApp();

        $this->assert->contains($this->actionIds(), 'FooList');
        $this->assert->not()->contains($this->actionIds(), 'DomainObjectList');
        $this->assert->not()->contains($this->actionIds(), 'ProjectionList');
    }
}