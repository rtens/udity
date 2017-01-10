<?php
namespace rtens\udity\app\ui\renderers;

use rtens\domin\delivery\RendererRegistry;
use rtens\domin\delivery\web\renderers\link\LinkPrinter;
use rtens\udity\AggregateIdentifier;
use rtens\udity\app\Application;
use rtens\udity\domain\query\IdentifierOptionsList;
use rtens\udity\Query;
use watoki\reflect\TypeFactory;

class CaptionedIdentifierRenderer extends IdentifierRenderer {
    /**
     * @var Application
     */
    private $app;
    /**
     * @var string
     */
    private $listClass;

    /**
     * @param Application $app
     * @param string $listClass
     * @param RendererRegistry $renderers
     * @param TypeFactory $types
     * @param LinkPrinter $linkPrinter
     */
    public function __construct($app, $listClass, $renderers, $types, $linkPrinter) {
        parent::__construct($renderers, $types, $linkPrinter);
        $this->app = $app;
        $this->listClass = $listClass;
    }

    protected function getCaption(AggregateIdentifier $identifier) {
        /** @var IdentifierOptionsList $optionsList */
        $optionsList = $this->app->execute(new Query($this->listClass));
        $options = $optionsList->options();

        $key = $identifier->getKey();
        if (array_key_exists($key, $options)) {
            return $options[$key];
        } else {
            return $key;
        }
    }
}