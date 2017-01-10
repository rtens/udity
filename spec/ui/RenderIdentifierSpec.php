<?php
namespace spec\rtens\udity\ui;

use rtens\domin\delivery\web\renderers\PrimitiveRenderer;
use rtens\udity\AggregateIdentifier;
use rtens\udity\Specification;

class RenderIdentifierSpec extends Specification {

    function noCaption() {
        $FooIdentifier = $this->define('FooIdentifier', AggregateIdentifier::class);

        $this->runApp();
        $this->domin->renderers->add(new PrimitiveRenderer());
        $renderer = $this->domin->renderers->getRenderer(new $FooIdentifier('foo'));
        $rendered = $renderer->render(new $FooIdentifier('foo'));

        $this->assert->not()->contains($rendered, '<dd>');
        $this->assert->contains($rendered, 'foo');
    }
}