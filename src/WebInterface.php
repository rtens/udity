<?php
namespace rtens\proto;

use rtens\domin\Action;
use rtens\domin\delivery\web\WebApplication;

class WebInterface {
    /**
     * @var Application
     */
    private $app;
    /**
     * @var WebApplication
     */
    private $ui;

    public function __construct(Application $app, WebApplication $ui) {
        $this->ui = $ui;
        $this->app = $app;
    }

    public function prepare() {
        $this->ui->types = new DefaultTypeFactory();

        $this->registerProjections();
        $this->registerAggregates();
        $this->registerDomainObjects();
    }

    private function registerProjections() {
        foreach ($this->findSubClasses(Projecting::class) as $projection) {
            $this->addQueryAction('Show', $projection);
        }
    }

    private function registerAggregates() {
        foreach ($this->findSubClasses(AggregateRoot::class) as $root) {
            $this->defineClassIfNotExists($root->getName() . 'Identifier', AggregateIdentifier::class);

            foreach ($this->findCommandMethods($root) as $command => $method) {
                $this->addCommandAction($command, $method);
            }
        }
    }

    private function registerDomainObjects() {
        foreach ($this->findSubClasses(DomainObject::class) as $object) {
            if ($object->hasMethod('created')) {
                $this->addCommandAction('create', $object->getMethod('created'));
            }

            $this->addQueryAction($object->getShortName(), $object, 'read');

            $this->defineClassIfNotExists($object->getName() . 'List', AggregateList::class);
            $this->addQueryAction($object->getShortName(), new \ReflectionClass($object->getName() . 'List'), 'all');
        }
    }

    /**
     * @param $baseClass
     * @return \Generator|\ReflectionClass[]
     */
    private function findSubClasses($baseClass) {
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, $baseClass)) {
                yield new \ReflectionClass($class);
            }
        }
    }

    /**
     * @param \ReflectionClass $rootClass
     * @return \ReflectionMethod[]
     */
    private function findCommandMethods(\ReflectionClass $rootClass) {
        $commandMethods = [];
        foreach ($rootClass->getMethods() as $method) {
            if ($method->getName() != 'handle' && substr($method->getName(), 0, strlen('handle')) == 'handle') {
                $commandMethods[substr($method->getName(), strlen('handle'))] = $method;
            }
        }
        return $commandMethods;
    }

    private function defineClassIfNotExists($fullName, $baseClass) {
        if (class_exists($fullName)) {
            return;
        }

        $parts = explode('\\', $fullName);
        $shortName = array_pop($parts);
        $nameSpace = implode('\\', $parts);

        eval("namespace $nameSpace; class $shortName extends \\" . $baseClass . " {}");
    }

    private function addCommandAction($command, \ReflectionMethod $method) {
        $class = $method->getDeclaringClass();
        $this->addAction($class->getShortName() . '$' . $command, $class->getShortName(),
            new CommandAction($this->app, $command, $method, $this->ui->types, $this->ui->parser));
    }

    private function addQueryAction($group, \ReflectionClass $class, $command = null) {
        $this->addAction($class->getShortName() . ($command ? '$' . $command : ''), $group,
            new QueryAction($this->app, $class->getName(), $this->ui->types, $this->ui->parser));
    }

    private function addAction($id, $group, Action $action) {
        $this->ui->actions->add($id, $action);
        $this->ui->groups->put($id, $group);
    }
}