<?php
namespace rtens\udity\app\ui\plugins;

use rtens\domin\delivery\web\renderers\link\LinkPrinter;
use rtens\domin\delivery\web\WebApplication;
use rtens\udity\AggregateIdentifier;
use rtens\udity\app\Application;
use rtens\udity\app\ui\fields\IdentifierEnumerationField;
use rtens\udity\app\ui\fields\IdentifierField;
use rtens\udity\app\ui\renderers\CaptionedIdentifierRenderer;
use rtens\udity\app\ui\renderers\IdentifierRenderer;
use rtens\udity\app\ui\WebInterfacePlugin;
use rtens\udity\utils\Str;

class AggregateIdentifierPlugin implements WebInterfacePlugin {
    /**
     * @var WebApplication
     */
    private $ui;
    /**
     * @var Application
     */
    private $app;
    /**
     * @var LinkPrinter
     */
    private $linkPrinter;

    /**
     * @param WebApplication $ui
     * @param Application $app
     */
    public function __construct(WebApplication $ui, Application $app) {
        $this->ui = $ui;
        $this->app = $app;

        $this->linkPrinter = new LinkPrinter($ui->links, $ui->actions, $ui->parser, $ui->token);
    }

    /**
     * @param string[] $domainClasses
     * @return void
     */
    public function prepare(array $domainClasses) {
        foreach ($domainClasses as $class) {
            if (!(Str::g($class)->endsWith('Identifier') && is_subclass_of($class, AggregateIdentifier::class))) {
                continue;
            }

            $listClass = Str::g($class)->before('Identifier') . 'List';
            if (in_array($listClass, $domainClasses)) {

                $this->ui->fields->add(new IdentifierEnumerationField($this->app, $listClass, $class));
                $this->ui->renderers->add(new CaptionedIdentifierRenderer($this->app, $listClass, $this->ui->renderers, $this->ui->types, $this->linkPrinter));
            } else {

                $this->ui->fields->add(new IdentifierField($class));
                $this->ui->renderers->add(new IdentifierRenderer($this->ui->renderers, $this->ui->types, $this->linkPrinter));
            }
        }
    }
}