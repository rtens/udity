<?php
namespace rtens\udity\app\ui\actions;

use rtens\domin\Parameter;
use rtens\domin\reflection\CommentParser;
use rtens\domin\reflection\StaticMethodAction;
use rtens\domin\reflection\types\TypeFactory;
use rtens\udity\app\Application;
use rtens\udity\Command;
use watoki\reflect\type\ClassType;

/**
 * Builds a Command from parameters inferred from a method
 */
class AggregateCommandAction extends StaticMethodAction {
    const IDENTIFIER_KEY = 'target';
    /**
     * @var Application
     */
    protected $app;
    /**
     * @var string
     */
    protected $name;

    /**
     * @param Application $app
     * @param string $name
     * @param \ReflectionMethod $method
     * @param TypeFactory $types
     * @param CommentParser $parser
     */
    public function __construct(Application $app, $name, \ReflectionMethod $method, TypeFactory $types, CommentParser $parser) {
        parent::__construct($method, $types, $parser);
        $this->app = $app;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function caption() {
        return $this->unCamelize($this->name);
    }

    /**
     * @return Parameter[]
     */
    protected function initParameters() {
        $class = $this->method->getDeclaringClass();
        $identifierClass = $class->getName() . 'Identifier';

        return [
            new Parameter(self::IDENTIFIER_KEY, new ClassType($identifierClass), true)
        ];
    }

    /**
     * @param mixed[] $parameters Values indexed by name
     * @return void
     * @throws \Exception if Action cannot be executed
     */
    public function execute(array $parameters) {
        $identifier = $parameters[self::IDENTIFIER_KEY];
        unset($parameters[self::IDENTIFIER_KEY]);

        $this->app->handle(new Command($identifier, $this->name, $parameters));
    }
}