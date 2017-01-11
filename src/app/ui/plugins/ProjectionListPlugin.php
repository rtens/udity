<?php
namespace rtens\udity\app\ui\plugins;

use rtens\domin\delivery\web\renderers\link\LinkPrinter;
use rtens\domin\delivery\web\WebApplication;
use rtens\udity\app\ui\renderers\ProjectionListRenderer;
use rtens\udity\app\ui\WebInterfacePlugin;

class ProjectionListPlugin implements WebInterfacePlugin {
    /**
     * @var WebApplication
     */
    private $ui;
    /**
     * @var LinkPrinter
     */
    private $linkPrinter;

    /**
     * @param WebApplication $ui
     */
    public function __construct(WebApplication $ui) {
        $this->ui = $ui;
        $this->linkPrinter = new LinkPrinter($ui->links, $ui->actions, $ui->parser, $ui->token);
    }

    /**
     * @param string[] $domainClasses
     * @return void
     */
    public function prepare(array $domainClasses) {
        $this->ui->renderers->add(new ProjectionListRenderer($this->ui->renderers, $this->ui->types, $this->linkPrinter));
    }
}