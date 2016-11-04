<?php
namespace rtens\proto\app\ui;

use rtens\domin\Action;
use rtens\proto\app\Application;
use rtens\proto\domain\command\Aggregate;
use rtens\proto\utils\Str;

class AggregateActionFactory implements ActionFactory {
    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app) {
        $this->app = $app;
    }

    /**
     * @return string
     */
    public function getClass() {
        return Aggregate::class;
    }

    /**
     * @param \ReflectionClass $class
     * @return Action[] indexed by their ID
     */
    public function buildActionsFrom(\ReflectionClass $class) {
        $actions = [];
        foreach ($this->findCommandMethods($class) as $command => $method) {
            $id = $this->makeActionId($class, $command);
            $actions[$id] = $this->buildCommandAction($command, $method);
        }
        return $actions;
    }

    /**
     * @param string $command
     * @param \ReflectionMethod $method
     * @return AggregateCommandAction
     */
    protected function buildCommandAction($command, \ReflectionMethod $method) {
        return new AggregateCommandAction($this->app, $command, $method);
    }

    /**
     * @param \ReflectionClass $aggregate
     * @return \ReflectionMethod[]
     */
    protected function findCommandMethods(\ReflectionClass $aggregate) {
        $commandMethods = [];
        foreach ($aggregate->getMethods() as $method) {
            $methodName = Str::g($method->getName());
            if ($methodName->startsWithButIsNot('handle')) {
                $commandMethods[$methodName->after('handle')] = $method;
            }
        }
        return $commandMethods;
    }

    protected function makeActionId(\ReflectionClass $class, $command) {
        return $class->getShortName() . ($command ? '$' . $command : '');
    }
}