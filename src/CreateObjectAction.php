<?php
namespace rtens\proto;

use rtens\domin\Action;
use rtens\domin\Parameter;
use rtens\domin\reflection\CommentParser;
use rtens\domin\reflection\types\TypeFactory;
use watoki\reflect\PropertyReader;

class CreateObjectAction implements Action {
    /**
     * @var Application
     */
    private $app;
    /**
     * @var \ReflectionClass
     */
    private $object;
    /**
     * @var TypeFactory
     */
    private $types;
    /**
     * @var CommentParser
     */
    private $parser;

    /**
     * @param Application $app
     * @param \ReflectionClass $object
     * @param TypeFactory $types
     * @param CommentParser $parser
     */
    public function __construct(Application $app, \ReflectionClass $object, TypeFactory $types, CommentParser $parser) {
        $this->app = $app;
        $this->object = $object;
        $this->types = $types;
        $this->parser = $parser;
    }

    /**
     * @return string
     */
    public function caption() {
        return "Create " . $this->object->getShortName();
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
        return true;
    }

    /**
     * @return Parameter[]
     */
    public function parameters() {
        $parameters = [];

        $reader = new PropertyReader($this->types, $this->object->getName());
        foreach ($reader->readInterface() as $property) {
            if ($property->name() == 'identifier') {
                continue;
            }

            $parameters[] = new Parameter($property->name(), $property->type(), $property->isRequired());
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
     * @return void the result of the execution
     * @throws \Exception if Action cannot be executed
     */
    public function execute(array $parameters) {
        $identifier = new GenericAggregateIdentifier($this->object->getName(), uniqid($this->object->getShortName()));
        $this->app->handle(new Command('create', $identifier, $parameters));
    }
}