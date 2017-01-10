<?php
namespace rtens\udity\app\ui\factories;

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

    protected function buildCommandAction($command, \ReflectionMethod $method) {
        return new SingletonCommandAction($this->app, $command, $method, $this->ui->types, $this->ui->parser);
    }
}