<?php
namespace rtens\udity\app\ui\factories;

use rtens\domin\delivery\web\renderers\link\types\ClassLink;
use rtens\udity\app\ui\actions\SingletonCommandAction;
use rtens\udity\domain\command\Singleton;

class SingletonActionFactory extends AggregateActionFactory {

    /**
     * @param \ReflectionClass $class
     * @return bool
     */
    public function handles(\ReflectionClass $class) {
        return $class->getParentClass() && $class->getParentClass()->getName() == Singleton::class;
    }

    public function buildActionsFrom(\ReflectionClass $class) {
        $actions = parent::buildActionsFrom($class);
        $this->linkActions($class, $actions);
        return $actions;
    }

    protected function buildCommandAction($command, \ReflectionMethod $method) {
        return new SingletonCommandAction($this->app, $command, $method, $this->ui->types, $this->ui->parser);
    }

    private function linkActions(\ReflectionClass $class, $actions) {
        foreach ($actions as $id => $action) {
            $this->ui->links->add(new ClassLink($class->getName(), $id));
        }
    }
}