<?php
namespace rtens\proto;

use rtens\domin\Action;
use rtens\domin\Parameter;
use rtens\domin\reflection\CommentParser;
use rtens\domin\reflection\types\TypeFactory;
use watoki\reflect\MethodAnalyzer;
use watoki\reflect\type\ClassType;

/**
 * Builds a Query from an Projection.
 */
class QueryAction implements Action {
    /**
     * @var Application
     */
    private $app;
    /**
     * @var \ReflectionClass
     */
    protected $class;
    /**
     * @var TypeFactory
     */
    private $types;
    /**
     * @var CommentParser
     * */
    private $parser;

    /**
     * @param Application $app
     * @param string $class
     * @param TypeFactory $types
     * @param CommentParser $parser
     */
    public function __construct(Application $app, $class, TypeFactory $types, CommentParser $parser) {
        $this->app = $app;
        $this->class = new \ReflectionClass($class);
        $this->types = $types;
        $this->parser = $parser;
    }

    /**
     * @return string
     */
    public function caption() {
        return preg_replace('/(.)([A-Z0-9])/', '$1 $2', $this->class->getShortName());
    }

    /**
     * @return string|null
     */
    public function description() {
        $docComment = $this->class->getDocComment();
        if (!$docComment) {
            return null;
        }

        return $this->parser->parse(implode("\n", array_map(function ($line) {
            return ltrim($line, " *\r\n\t");
        }, array_slice(explode("\n", $docComment), 1, -1))));
    }

    /**
     * Fills out partially available parameters
     *
     * @param array $parameters Available values indexed by name
     * @return array Filled values indexed by name
     */
    public function fill(array $parameters) {
        if (!$this->class->getConstructor()) {
            return $parameters;
        }

        foreach ($this->class->getConstructor()->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable() && !array_key_exists($parameter->name, $parameters)) {
                $parameters[$parameter->name] = $parameter->getDefaultValue();
            }
        }
        return $parameters;
    }

    /**
     * @return Parameter[]
     * @throws \Exception
     */
    public function parameters() {
        if (!$this->class->getConstructor()) {
            return [];
        }

        $analyzer = new MethodAnalyzer($this->class->getConstructor());
        $parameters = [];

        foreach ($this->class->getConstructor()->getParameters() as $parameter) {
            $type = $analyzer->getType($parameter, $this->types);
            if ($parameter->getName() == 'identifier') {
                $type = new ClassType($this->class->getName() . 'Identifier');
            }
            $parameters[] = (new Parameter($parameter->name, $type, !$parameter->isDefaultValueAvailable()))
                ->setDescription($this->parser->parse($analyzer->getComment($parameter)));
        }
        return $parameters;
    }

    /**
     * @return boolean True if the action modifies the state of the application
     */
    public function isModifying() {
        return false;
    }

    /**
     * @param mixed[] $parameters Values indexed by name
     * @return mixed the result of the execution
     * @throws \Exception if Action cannot be executed
     */
    public function execute(array $parameters) {
        return $this->app->handle(new Query($this->class->getName(), $parameters));
    }
}