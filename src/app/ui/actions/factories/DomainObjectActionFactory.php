<?php
namespace rtens\proto\app\ui\actions\factories;

use rtens\domin\Action;
use rtens\domin\delivery\web\WebApplication;
use rtens\proto\app\Application;
use rtens\proto\app\ui\ActionFactory;
use rtens\proto\app\ui\actions\CreateDomainObjectAction;
use rtens\proto\app\ui\actions\QueryAction;
use rtens\proto\domain\objects\DomainObject;

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
            $actions[$this->id($class, 'create')] = new CreateDomainObjectAction($this->app, $class->getMethod('created'), $this->ui->types);
        }

        $listClass = new \ReflectionClass($class->getName() . 'List');
        $actions[$this->id($listClass, 'all')] = new QueryAction($this->app, $listClass, $this->ui->types);

//        foreach ($class->getMethods() as $method) {
//            $methodName = Str::g($method->getName());
//
//            if ($methodName->startsWithButIsNot('set') && $method->getNumberOfParameters() == 1) {
//                $this->addCommandAction($method, 'change' . $methodName->after('set'))
//                    ->setPostFill($this->fillPropertyFunction($method));
//
//            } else if ($methodName->startsWithButIsNot('do')) {
//                $this->addCommandAction($method, $methodName);
//
//            } else if ($methodName->startsWithButIsNot('did')) {
//                $command = 'do' . $methodName->after('did');
//                if (!array_key_exists($class->getShortName() . '$' . $command, $this->ui->actions->getAllActions())) {
//                    $this->addCommandAction($method, $command);
//                }
//            }
//        }

        return $actions;
    }

    private function id(\ReflectionClass $class, $command) {
        return $class->getShortName() . ($command ? '$' . $command : '');
    }
}