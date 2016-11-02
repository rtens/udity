<?php
namespace rtens\proto;

use rtens\domin\Action;
use rtens\domin\Parameter;
use rtens\domin\reflection\CommentParser;
use rtens\domin\reflection\types\TypeFactory;
use watoki\reflect\MethodAnalyzer;
use watoki\reflect\type\ClassType;
use watoki\reflect\type\StringType;

/**
 * Builds a Command from an AggregateRoot's method.
 */
class CommandAction implements Action {
    const IDENTIFIER_KEY = 'target';

    /**
     * @var \ReflectionMethod|null
     */
    private $method;
    /**
     * @var TypeFactory
     */
    private $types;
    /**
     * @var CommentParser
     */
    private $parser;
    /**
     * @var Application
     */
    private $app;
    /**
     * @var string
     */
    private $commandName;
    /**
     * @var callable
     */
    private $postFill;
    /**
     * @var callable
     */
    private $transformParameters;

    /**
     * @param Application $app
     * @param string $commandName
     * @param \ReflectionMethod|null $method
     * @param TypeFactory $types
     * @param CommentParser $parser
     */
    public function __construct(Application $app, $commandName, \ReflectionMethod $method = null, TypeFactory $types, CommentParser $parser) {
        $this->app = $app;
        $this->commandName = $commandName;
        $this->method = $method;
        $this->types = $types;
        $this->parser = $parser;

        $this->postFill = function ($parameters) {
            return $parameters;
        };
        $this->transformParameters = function ($parameters) {
            return $parameters;
        };
    }

    public function setPostFill(callable $fill) {
        $this->postFill = $fill;
        return $this;
    }

    public function setTransformParameters(callable $transformer) {
        $this->transformParameters = $transformer;
        return $this;
    }

    /**
     * @return string
     */
    public function caption() {
        return $this->unCamelize($this->commandName);
    }

    private function unCamelize($camel) {
        return ucfirst(preg_replace('/(.)([A-Z0-9])/', '$1 $2', $camel));
    }

    /**
     * @return string|null
     */
    public function description() {
        $lines = array_slice(explode("\n", $this->method->getDocComment()), 1, -1);
        $lines = array_map(function ($line) {
            return ltrim($line, ' *');
        }, $lines);
        $lines = array_filter($lines, function ($line) {
            return substr($line, 0, 1) != '@';
        });
        return $this->parser->parse(trim(implode("\n", $lines)));
    }

    /**
     * @return Parameter[]
     */
    public function parameters() {
        $analyzer = new MethodAnalyzer($this->method);
        $parameters = [];

        $class = $this->method->getDeclaringClass();

        if (!$class->isSubclassOf(SingletonAggregateRoot::class) && $this->commandName != 'create') {
            $identifierClass = $class->getName() . 'Identifier';
            if (class_exists($identifierClass) && is_subclass_of($identifierClass, AggregateIdentifier::class)) {
                $parameters[] = new Parameter(self::IDENTIFIER_KEY, new ClassType($identifierClass), true);
            } else {
                $parameters[] = new Parameter(self::IDENTIFIER_KEY, new StringType(), true);
            }
        }

        foreach ($this->method->getParameters() as $parameter) {
            $type = $analyzer->getType($parameter, $this->types);
            $parameters[] = (new Parameter($parameter->name, $type, !$parameter->isDefaultValueAvailable()))
                ->setDescription($this->parser->parse($analyzer->getComment($parameter)));
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
        foreach ($this->method->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable() && !array_key_exists($parameter->name, $parameters)) {
                $parameters[$parameter->name] = $parameter->getDefaultValue();
            }
        }
        return call_user_func($this->postFill, $parameters);
    }

    /**
     * @param mixed[] $parameters Values indexed by name
     * @return void
     * @throws \Exception if Action cannot be executed
     */
    public function execute(array $parameters) {
        $class = $this->method->getDeclaringClass();
        $identifierClass = $class->getName() . 'Identifier';

        if ($this->commandName == 'create') {
            $identifier = new $identifierClass(uniqid($class->getShortName()));
        } else if ($class->isSubclassOf(SingletonAggregateRoot::class)) {
            $identifier = new $identifierClass($class->getShortName());
        } else if (array_key_exists(self::IDENTIFIER_KEY, $parameters)) {
            $identifier = $parameters[self::IDENTIFIER_KEY];
            unset($parameters[self::IDENTIFIER_KEY]);
        } else {
            throw new \Exception('Missing identifier for this command');
        }

        $parameters = call_user_func($this->transformParameters, $parameters);
        $this->app->handle(new Command($this->commandName, $identifier, $parameters));
    }

    /**
     * @return boolean True if the action modifies the state of the application
     */
    public function isModifying() {
        return true;
    }
}