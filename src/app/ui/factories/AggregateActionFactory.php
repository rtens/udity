<?php
namespace rtens\udity\app\ui\factories;

use rtens\domin\Action;
use rtens\domin\delivery\web\WebApplication;
use rtens\udity\app\Application;
use rtens\udity\app\ui\ActionFactory;
use rtens\udity\app\ui\actions\AggregateCommandAction;
use rtens\udity\domain\command\Aggregate;
use rtens\udity\utils\Str;

class AggregateActionFactory implements ActionFactory {
    /**
     * @var Application
     */
    protected $app;
    /**
     * @var WebApplication
     */
    protected $ui;

    /**
     * @param Application $app
     * @param WebApplication $ui
     */
    public function __construct(Application $app, WebApplication $ui) {
        $this->app = $app;
        $this->ui = $ui;
    }

    /**
     * @param \ReflectionClass $class
     * @return bool
     */
    public function handles(\ReflectionClass $class) {
        return $class->getParentClass() && $class->getParentClass()->getName() == Aggregate::class;
    }

    /**
     * @param \ReflectionClass $class
     * @return Action[] indexed by their ID
     */
    public function buildActionsFrom(\ReflectionClass $class) {
        $actions = [];
        foreach ($this->findCommandMethods($class) as $command => $method) {
            $actions[$this->id($class, $command)] = $this->buildCommandAction($command, $method);
        }
        return $actions;
    }

    /**
     * @param string $command
     * @param \ReflectionMethod $method
     * @return AggregateCommandAction
     */
    protected function buildCommandAction($command, \ReflectionMethod $method) {
        return new AggregateCommandAction($this->app, $command, $method, $this->ui->types);
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

    private function id(\ReflectionClass $class, $command) {
        return $class->getShortName() . ($command ? '$' . $command : '');
    }
}