<?php
namespace rtens\proto\app\ui\actions\factories;

use rtens\domin\Action;
use rtens\domin\delivery\web\WebApplication;
use rtens\proto\app\Application;
use rtens\proto\app\ui\ActionFactory;
use rtens\proto\app\ui\actions\AggregateCommandAction;
use rtens\proto\app\ui\actions\ChangeDomainObjectAction;
use rtens\proto\app\ui\actions\CreateDomainObjectAction;
use rtens\proto\app\ui\actions\QueryAction;
use rtens\proto\domain\objects\DomainObject;
use rtens\proto\utils\Str;

class DomainObjectActionFactory implements ActionFactory {
    /**
     * @var Application
     */
    private $app;
    /**
     * @var WebApplication
     */
    private $ui;

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
        return $class->getParentClass() && $class->getParentClass()->getName() == DomainObject::class;
    }

    /**
     * @param \ReflectionClass $class
     * @return Action[] indexed by their ID
     */
    public function buildActionsFrom(\ReflectionClass $class) {
        $actions = [];

        if ($class->hasMethod('created')) {
            $actions[$this->id($class, 'create')] =
                new CreateDomainObjectAction($this->app, $class->getMethod('created'), $this->ui->types);
        }

        $listClass = new \ReflectionClass($class->getName() . 'List');
        $actions[$this->id($listClass, 'all')] = new QueryAction($this->app, $listClass, $this->ui->types);

        foreach ($class->getMethods() as $method) {
            $methodName = Str::g($method->getName());

            if ($methodName->startsWithButIsNot('set') && $method->getNumberOfParameters() == 1) {
                $command = 'change' . $methodName->after('set');
                $actions[$this->id($class, $command)] =
                    new ChangeDomainObjectAction($this->app, $command, $method, $this->ui->types);

            } else if ($methodName->startsWithButIsNot('do')) {
                $this->addCommand($class, $method->getName(), $method, $actions);

            } else if ($methodName->startsWithButIsNot('did')) {
                $this->addCommand($class, 'do' . $methodName->after('did'), $method, $actions);
            }
        }

        return $actions;
    }

    private function id(\ReflectionClass $class, $command) {
        return $class->getShortName() . ($command ? '$' . $command : '');
    }

    private function addCommand(\ReflectionClass $class, $command, $method, &$actions) {
        $actions[$this->id($class, $command)] =
            new AggregateCommandAction($this->app, $command, $method, $this->ui->types);
    }
}