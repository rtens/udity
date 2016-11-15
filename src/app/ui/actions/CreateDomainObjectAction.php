<?php
namespace rtens\udity\app\ui\actions;

use rtens\domin\Parameter;
use rtens\domin\reflection\types\TypeFactory;
use rtens\udity\app\Application;
use rtens\udity\Command;

class CreateDomainObjectAction extends AggregateCommandAction {

    /**
     * @param Application $app
     * @param \ReflectionMethod $method
     * @param TypeFactory $types
     */
    public function __construct(Application $app, $method, TypeFactory $types) {
        parent::__construct($app, 'create', $method, $types);
    }

    /**
     * @return Parameter[]
     */
    protected function initParameters() {
        return [];
    }

    public function execute(array $parameters) {
        $class = $this->method->getDeclaringClass();
        $identifierClass = $class->getName() . 'Identifier';
        $identifier = new $identifierClass(uniqid($class->getShortName()));

        $this->app->handle(new Command($identifier, $this->name, $parameters));
    }
}