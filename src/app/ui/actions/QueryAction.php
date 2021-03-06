<?php
namespace rtens\udity\app\ui\actions;

use rtens\domin\Action;
use rtens\domin\Parameter;
use rtens\domin\reflection\types\TypeFactory;
use rtens\udity\AggregateIdentifier;
use rtens\udity\app\Application;
use rtens\udity\Query;
use watoki\reflect\MethodAnalyzer;
use watoki\reflect\type\ClassType;

/**
 * Builds a Query with parameters inferred from a class
 */
class QueryAction implements Action {
    /**
     * @var Application
     */
    private $app;
    /**
     * @var \ReflectionClass
     */
    private $class;
    /**
     * @var TypeFactory
     */
    private $types;

    /**
     * @param Application $app
     * @param \ReflectionClass $class
     * @param TypeFactory $types
     */
    public function __construct(Application $app, \ReflectionClass $class, TypeFactory $types) {
        $this->app = $app;
        $this->class = $class;
        $this->types = $types;
    }

    /**
     * @return string
     */
    public function caption() {
        return 'Show ' . ucfirst(preg_replace('/(.)([A-Z0-9])/', '$1 $2', $this->class->getShortName()));
    }

    /**
     * @return string|null
     */
    public function description() {
        return null;
    }

    /**
     * @return boolean True if the action modifies the state of the application
     */
    public function isModifying() {
        return false;
    }

    /**
     * @return Parameter[]
     */
    public function parameters() {
        $parameters = [];

        $constructor = $this->class->getConstructor();
        if (!$constructor) {
            return $parameters;
        }

        $analyzer = new MethodAnalyzer($constructor);
        foreach ($constructor->getParameters() as $parameter) {
            $type = $analyzer->getType($parameter, $this->types);
            $required = !$parameter->isDefaultValueAvailable();

            if ($parameter->getName() == 'identifier' && $type == new ClassType(AggregateIdentifier::class)) {
                $type = new ClassType($this->class->getName() . 'Identifier');
            }

            $parameters[] = (new Parameter($parameter->name, $type, $required));
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
        $constructor = $this->class->getConstructor();
        if (!$constructor) {
            return $parameters;
        }

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable() && !array_key_exists($parameter->name, $parameters)) {
                $parameters[$parameter->name] = $parameter->getDefaultValue();
            }
        }
        return $parameters;
    }

    /**
     * @param mixed[] $parameters Values indexed by name
     * @return mixed the result of the execution
     * @throws \Exception if Action cannot be executed
     */
    public function execute(array $parameters) {
        return $this->app->execute(new Query($this->class->getName(), $parameters));
    }
}