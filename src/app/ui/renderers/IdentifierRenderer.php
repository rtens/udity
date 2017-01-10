<?php
namespace rtens\udity\app\ui\renderers;

use rtens\domin\delivery\RendererRegistry;
use rtens\domin\delivery\web\Element;
use rtens\domin\delivery\web\renderers\link\LinkPrinter;
use rtens\domin\delivery\web\renderers\ObjectRenderer;
use rtens\udity\AggregateIdentifier;
use watoki\reflect\TypeFactory;

class IdentifierRenderer extends ObjectRenderer {
    /**
     * @var RendererRegistry
     */
    private $renderers;
    /**
     * @var LinkPrinter
     */
    private $links;

    public function __construct(RendererRegistry $renderers, TypeFactory $types, LinkPrinter $links) {
        parent::__construct($renderers, $types, $links);
        $this->renderers = $renderers;
        $this->links = $links;
    }

    public function handles($value) {
        return $value instanceof AggregateIdentifier;
    }

    /**
     * @param AggregateIdentifier $identifier
     * @return string
     */
    protected function getCaption(AggregateIdentifier $identifier) {
        return $identifier->getKey();
    }

    /**
     * @param object|AggregateIdentifier $value
     * @return mixed
     */
    public function render($value) {
        if (method_exists($value, '__toString')) {
            return (string)$value;
        }

        return (string)new Element('div', ['class' => 'alert alert-info'], [
            $this->getCaption($value),
            new Element('small', ['class' => 'pull-right'], $this->links->createDropDown($value))
        ]);
    }

    public function headElements($value) {
        return [];
    }
}