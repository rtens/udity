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

        if ($class->hasMethod('create')) {
            $createMethod = $class->getMethod('create');
        } else if ($class->hasMethod('created')) {
            $createMethod = $class->getMethod('created');
        }

        if (isset($createMethod)) {
            $actions[$this->id($class, 'create')] =
                new CreateDomainObjectAction($this->app, $createMethod, $this->ui->types, $this->ui->parser);
        }

        $listClass = new \ReflectionClass($class->getName() . 'List');
        $actions[$this->id($listClass)] = new QueryAction($this->app, $listClass, $this->ui->types);

        $actions = $this->buildActionsFromMethods($class, $actions);

        return $actions;
    }

    private function buildActionsFromMethods(\ReflectionClass $class, $actions) {
        foreach ($class->getMethods() as $method) {
            $methodName = Str::g($method->getName());

            if ($methodName->startsWithButIsNot('set') && $method->getNumberOfParameters() == 1) {
                $command = 'change' . $methodName->after('set');
                $actions[$this->id($class, $command)] =
                    new ChangeDomainObjectAction($this->app, $command, $method, $this->ui->types, $this->ui->parser);

            } else if ($methodName->startsWithButIsNot('do')) {
                $this->addCommand($class, $method->getName(), $method, $actions);

            } else if ($methodName->startsWithButIsNot('did')) {
                $this->addCommand($class, 'do' . $methodName->after('did'), $method, $actions);
            }
        }
        return $actions;
    }

    private function id(\ReflectionClass $class, $command = null) {
        return $class->getShortName() . ($command ? '$' . $command : '');
    }

    private function addCommand(\ReflectionClass $class, $command, $method, &$actions) {
        $actionId = $this->id($class, $command);
        if (array_key_exists($actionId, $actions)) {
            return;
        }

        $action = new GenericAction(new AggregateCommandAction($this->app, $command, $method, $this->ui->types, $this->ui->parser));
        $action->setCaption(preg_replace("/^Do/", "", $action->caption()));
        $actions[$actionId] = $action;
    }
}