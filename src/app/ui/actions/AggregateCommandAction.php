<?php
namespace rtens\proto\app\ui\actions;

use rtens\domin\Action;
use rtens\domin\Parameter;
use rtens\domin\reflection\types\TypeFactory;
use rtens\proto\app\Application;
use rtens\proto\Command;
use watoki\reflect\MethodAnalyzer;
use watoki\reflect\type\ClassType;

/**
 * Builds a Command from parameters inferred from a method
 */
class AggregateCommandAction implements Action {
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
     * @var \ReflectionMethod
     */
    protected $method;
    /**
     * @var TypeFactory
     */
    private $types;

    /**
     * @param Application $app
     * @param string $name
     * @param \ReflectionMethod $method
     * @param TypeFactory $types
     */
    public function __construct(Application $app, $name, \ReflectionMethod $method, TypeFactory $types) {
        $this->app = $app;
        $this->name = $name;
        $this->method = $method;
        $this->types = $types;
    }

    /**
     * @return string
     */
    public function caption() {
        return '';
    }

    /**
     * @return string|null
     */
    public function description() {
        return '';
    }

    /**
     * @return boolean True if the action modifies the state of the application
     */
    public function isModifying() {
        return true;
    }

    /**
     * @return Parameter[]
     */
    public function parameters() {
        $parameters = $this->initParameters();


        $analyzer = new MethodAnalyzer($this->method);
        foreach ($this->method->getParameters() as $parameter) {
            $type = $analyzer->getType($parameter, $this->types);
            $parameters[] = (new Parameter($parameter->name, $type, !$parameter->isDefaultValueAvailable()));
        }

        return $parameters;
    }

    /**
     * Fills out partially available parameters
     *
     * @param array $parameters Available values indexed by name
     * @return array Filled values indexed by name
     */
    public function fill(array $parameters) {
        return $parameters;
    }

    /**
     * @param mixed[] $parameters Values indexed by name
     * @return void
     * @throws \Exception if Action cannot be executed
     */
    public function execute(array $parameters) {
        $identifier = $parameters[self::IDENTIFIER_KEY];

        $this->app->handle(new Command($identifier, $this->name, $parameters));
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
}