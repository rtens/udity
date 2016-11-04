<?php
namespace rtens\proto\app\ui;

use rtens\domin\delivery\web\WebApplication;
use rtens\proto\app\Application;
use rtens\proto\app\ui\actions\factories\AggregateActionFactory;
use rtens\proto\app\ui\actions\factories\ProjectionActionFactory;
use rtens\proto\app\ui\actions\factories\SingletonActionFactory;
use rtens\proto\app\ui\actions\factories\DomainObjectActionFactory;

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
     * @param Application $app
     * @param WebApplication $ui
     */
    public function __construct(Application $app, WebApplication $ui) {
        $this->app = $app;
        $this->ui = $ui;
    }

    /**
     * @return ActionFactory[]
     */
    protected function buildActionFactories() {
        return [
            new ProjectionActionFactory($this->app, $this->ui),
            new SingletonActionFactory($this->app, $this->ui),
            new DomainObjectActionFactory($this->app, $this->ui),
            new AggregateActionFactory($this->app, $this->ui),
        ];
    }

    /**
     * @param string[] $domainClasses
     */
    public function prepare(array $domainClasses) {
        $this->ui->types = new DefaultTypeFactory();

        $this->registerActions($domainClasses);
    }

    private function registerActions(array $domainClasses) {
        foreach ($domainClasses as $class) {
            $this->registerActionsOf(new \ReflectionClass($class));
        }
    }

    private function registerActionsOf($class) {
        foreach ($this->buildActionFactories() as $factory) {
            if ($factory->handles($class)) {
                $this->registerActionBuiltBy($class, $factory);
            }
        }
    }

    private function registerActionBuiltBy(\ReflectionClass $class, ActionFactory $factory) {
        foreach ($factory->buildActionsFrom($class) as $id => $action) {
            $this->ui->actions->add($id, $action);
        }
    }
}