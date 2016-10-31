<?php
namespace rtens\proto;

use rtens\domin\Action;
use rtens\domin\Parameter;
use rtens\domin\reflection\CommentParser;
use rtens\domin\reflection\types\TypeFactory;
use watoki\reflect\PropertyReader;
use watoki\reflect\type\StringType;

class QueryAction implements Action {
    /**
     * @var Application
     */
    private $app;
    /**
     * @var PropertyReader
     */
    private $reader;
    /**
     * @var \ReflectionClass
     */
    protected $class;
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
        $this->reader = new PropertyReader($types, $class);
        $this->class = new \ReflectionClass($class);
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
        foreach ($this->reader->readInterface() as $property) {
            if (!array_key_exists($property->name(), $parameters)) {
                $parameters[$property->name()] = $property->defaultValue();
            }
        }
        return $parameters;
    }

    /**
     * @return Parameter[]
     * @throws \Exception
     */
    public function parameters() {
        $parameters = [];
        foreach ($this->reader->readInterface() as $property) {
            if ($property->canSet()) {
                $type = $property->type();
                if ($property->name() == 'identifier') {
                    $type = new StringType();
                }

                $parameters[] = (new Parameter($property->name(), $type, $property->isRequired()))
                    ->setDescription($this->parser->parse($property->comment()));
            }
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
        if (array_key_exists('identifier', $parameters) && is_string($parameters['identifier'])) {
            $parameters['identifier'] = new GenericAggregateIdentifier($this->class->getName(), $parameters['identifier']);
        }
        return $this->app->handle(new Query($this->class->getName(), $parameters));
    }
}