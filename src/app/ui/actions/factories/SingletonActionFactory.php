<?php
namespace rtens\proto\app\ui\actions\factories;

use rtens\proto\app\ui\actions\SingletonCommandAction;
use rtens\proto\domain\command\Singleton;

class SingletonActionFactory extends AggregateActionFactory {

    /**
     * @param \ReflectionClass $class
     * @return bool
     */
    public function handles(\ReflectionClass $class) {
        return $class->getParentClass() && $class->getParentClass()->getName() == Singleton::class;
    }

    protected function buildCommandAction($command, \ReflectionMethod $method) {
        return new SingletonCommandAction($this->app, $command, $method, $this->ui->types);
    }
}