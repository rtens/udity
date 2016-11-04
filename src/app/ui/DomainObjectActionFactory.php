<?php
namespace rtens\proto\app\ui;

use rtens\domin\Action;
use rtens\domin\delivery\web\WebApplication;
use rtens\proto\app\Application;
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

    public function getClass() {
        return DomainObject::class;
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

//        $this->addQueryAction($class);
//
//        $this->defineClassIfNotExists($class->getName() . 'List', AggregateList::class);
//        $this->addQueryAction(new \ReflectionClass($class->getName() . 'List'), 'all');
//
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