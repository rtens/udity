<?php
namespace spec\rtens\udity\ui;

use rtens\domin\delivery\cli\renderers\ArrayRenderer;
use rtens\udity\domain\query\DefaultProjection;
use rtens\udity\domain\query\ProjectionList;
use rtens\udity\Event;
use rtens\udity\Specification;

class RenderListsSpec extends Specification {

    function projectionList() {
        $FooList = $this->define('FooList', ProjectionList::class, '
            protected function matchesEvent(\\' . Event::class . ' $event) {
                return true;
            }
            protected function createItem(\\' . Event::class . ' $event) { 
                return new \\' . DefaultProjection::class . ';
            }
        ');

        $this->runApp();
        $this->domin->renderers->add(new ArrayRenderer($this->domin->renderers));
        $renderer = $this->domin->renderers->getRenderer(new $FooList());

        $this->assert->not($renderer->handles(new \DateTime()));

        $rendered = $renderer->render(new $FooList());
        $this->assert(trim($rendered), "");
    }
}