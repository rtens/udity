<?php
namespace rtens\udity\app\ui;

use rtens\domin\delivery\web\WebApplication;
use rtens\udity\app\Application;
use rtens\udity\app\ui\factories\AggregateActionFactory;
use rtens\udity\app\ui\factories\DomainObjectActionFactory;
use rtens\udity\app\ui\factories\ProjectionActionFactory;
use rtens\udity\app\ui\factories\SingletonActionFactory;
use rtens\udity\app\ui\plugins\AggregateIdentifierPlugin;
use rtens\udity\app\ui\plugins\LinkActionsPlugin;
use rtens\udity\app\ui\plugins\ProjectionListPlugin;
use rtens\udity\app\ui\plugins\RegisterActionsPlugin;

/**
 * Prepares the web interface (e.g. registers Actions, Links and Field)
 */
class WebInterface {
    /**
     * @var Application
     */
    private $app;
    /**
     * @var WebApplication
     */
    private $ui;
    /**
     * @var WebInterfacePlugin[]
     */
    private $plugins = [];

    /**
     * @param Application $app
     * @param WebApplication $ui
     */
    public function __construct(Application $app, WebApplication $ui) {
        $this->app = $app;
        $this->ui = $ui;

        $this->plugins = [
            new RegisterActionsPlugin($ui, $this->buildActionFactories()),
            new AggregateIdentifierPlugin($ui, $app),
            new ProjectionListPlugin($ui),
            new LinkActionsPlugin($ui, $app)
        ];
    }

    /**
     * @return ActionFactory[]
     */
    protected function buildActionFactories() {
        return [
            new SingletonActionFactory($this->app, $this->ui),
            new DomainObjectActionFactory($this->app, $this->ui),
            new AggregateActionFactory($this->app, $this->ui),
            new ProjectionActionFactory($this->app, $this->ui),
        ];
    }

    /**
     * @param string[] $domainClasses
     */
    public function prepare(array $domainClasses) {
        $this->ui->types = new DefaultTypeFactory();

        foreach ($this->plugins as $plugin) {
            $plugin->prepare($domainClasses);
        }
    }
}