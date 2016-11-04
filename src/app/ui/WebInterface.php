<?php
namespace rtens\proto\app\ui;
use rtens\domin\delivery\web\WebApplication;
use rtens\proto\app\Application;

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
     * @var ActionFactory[]
     */
    private $factories = [];

    /**
     * @param Application $app
     * @param WebApplication $ui
     */
    public function __construct(Application $app, WebApplication $ui) {
        $this->app = $app;
        $this->ui = $ui;
        $this->factories = $this->buildActionFactories();
    }

    /**
     * @return ActionFactory[]
     */
    protected function buildActionFactories() {
        return [
            new AggregateActionFactory($this->app),
            new SingletonActionFactory($this->app)
        ];
    }

    public function prepare(array $domainClasses) {
        foreach ($domainClasses as $class) {
            $class = new \ReflectionClass($class);

            $parent = $class->getParentClass();
            if (!$parent) {
                continue;
            }

            foreach ($this->factories as $factory) {
                if ($factory->getClass() == $parent->getName()) {
                    foreach ($factory->buildActionsFrom($class) as $id => $action) {
                        $this->ui->actions->add($id, $action);
                    }
                    break;
                }
            }
        }
    }
}