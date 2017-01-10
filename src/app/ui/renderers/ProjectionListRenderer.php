<?php
namespace rtens\udity\app\ui\renderers;

use rtens\domin\delivery\RendererRegistry;
use rtens\domin\delivery\web\renderers\link\LinkPrinter;
use rtens\domin\delivery\web\renderers\ObjectRenderer;
use rtens\udity\domain\query\ProjectionList;
use watoki\reflect\TypeFactory;

class ProjectionListRenderer extends ObjectRenderer {

    /**
     * @var RendererRegistry
     */
    private $renderers;

    public function __construct(RendererRegistry $renderers, TypeFactory $types, LinkPrinter $links) {
        parent::__construct($renderers, $types, $links);
        $this->renderers = $renderers;
    }

    public function handles($value) {
        return $value instanceof ProjectionList;
    }

    /**
     * @param object|ProjectionList $value
     * @return mixed
     */
    public function render($value) {
        return $this->renderers->getRenderer($value->getList())->render($value->getList());
    }
}