<?php
namespace rtens\proto\app\ui;

use rtens\proto\domain\command\Singleton;

class SingletonActionFactory extends AggregateActionFactory {

    /**
     * @return string
     */
    public function getClass() {
        return Singleton::class;
    }

    protected function buildCommandAction($command, \ReflectionMethod $method) {
        return new SingletonCommandAction($this->app, $command, $method, $this->ui->types);
    }
}