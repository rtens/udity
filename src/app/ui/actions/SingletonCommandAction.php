<?php
namespace rtens\udity\app\ui\actions;

use rtens\udity\Command;

class SingletonCommandAction extends AggregateCommandAction {

    protected function initParameters() {
        return [];
    }

    /**
     * @param mixed[] $parameters Values indexed by name
     * @return void
     * @throws \Exception if Action cannot be executed
     */
    public function execute(array $parameters) {
        $class = $this->method->getDeclaringClass();
        $identifierClass = $class->getName() . 'Identifier';
        $identifier = new $identifierClass($class->getShortName());

        $this->app->handle(new Command($identifier, $this->name, $parameters));
    }
}