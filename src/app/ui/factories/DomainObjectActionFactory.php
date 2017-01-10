<?php
namespace rtens\udity\app\ui\factories;

use rtens\domin\Action;
use rtens\domin\delivery\web\WebApplication;
use rtens\domin\reflection\GenericAction;
use rtens\udity\app\Application;
use rtens\udity\app\ui\ActionFactory;
use rtens\udity\app\ui\actions\AggregateCommandAction;
use rtens\udity\app\ui\actions\ChangeDomainObjectAction;
use rtens\udity\app\ui\actions\CreateDomainObjectAction;
use rtens\udity\app\ui\actions\QueryAction;
use rtens\udity\domain\objects\DomainObject;
use rtens\udity\utils\Str;

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

        $actions = $this->buildActionsFromMethods($class, $actions);

        return $actions;
    }

    private function buildActionsFromMethods(\ReflectionClass $class, $actions) {
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
        $action = new GenericAction(new AggregateCommandAction($this->app, $command, $method, $this->ui->types));
        $action->setCaption(preg_replace("/^Do/", "", $action->caption()));
        $actions[$this->id($class, $command)] = $action;
    }
}