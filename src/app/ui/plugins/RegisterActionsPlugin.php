<?php
namespace rtens\udity\app\ui\plugins;

use rtens\domin\delivery\web\WebApplication;
use rtens\udity\app\ui\ActionFactory;
use rtens\udity\app\ui\WebInterfacePlugin;

class RegisterActionsPlugin implements WebInterfacePlugin {
    /**
     * @var ActionFactory[]
     */
    private $factories;
    /**
     * @var WebApplication
     */
    private $ui;

    /**
     * @param WebApplication $ui
     * @param ActionFactory[] $factories
     */
    public function __construct(WebApplication $ui, array $factories) {
        $this->factories = $factories;
        $this->ui = $ui;
    }

    /**
     * @param string[] $domainClasses
     * @return void
     */
    public function prepare(array $domainClasses) {
        foreach ($domainClasses as $class) {
            $this->registerActionsOf(new \ReflectionClass($class));
        }
    }

    private function registerActionsOf($class) {
        foreach ($this->factories as $factory) {
            if ($factory->handles($class)) {
                $this->registerActionBuiltBy($class, $factory);
            }
        }
    }

    private function registerActionBuiltBy(\ReflectionClass $class, ActionFactory $factory) {
        foreach ($factory->buildActionsFrom($class) as $id => $action) {
            $this->ui->actions->add($id, $action);
            $this->ui->groups->put($id, $class->getShortName());
        }
    }
}