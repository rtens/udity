<?php
namespace rtens\udity\app\ui\actions;

use rtens\domin\Parameter;
use rtens\domin\reflection\CommentParser;
use rtens\domin\reflection\types\TypeFactory;
use rtens\udity\app\Application;
use rtens\udity\Command;

class CreateDomainObjectAction extends AggregateCommandAction {

    /**
     * @param Application $app
     * @param \ReflectionMethod $method
     * @param TypeFactory $types
     * @param CommentParser $parser
     */
    public function __construct(Application $app, \ReflectionMethod $method, TypeFactory $types, CommentParser $parser) {
        parent::__construct($app, 'create', $method, $types, $parser);
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