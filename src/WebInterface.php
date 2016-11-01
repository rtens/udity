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
            $this->addAction($projection->getShortName(), 'Show',
                new QueryAction($this->app, $projection->getName(), $this->ui->types, $this->ui->parser));
        }
    }

    private function registerAggregates() {
        foreach ($this->findSubClasses(AggregateRoot::class) as $root) {
            $this->defineClassIfNotExists($root->getName() . 'Identifier', AggregateIdentifier::class);

            foreach ($this->findCommandMethods($root) as $command => $method) {
                $this->addAction($root->getShortName() . '$' . $command, $root->getShortName(),
                    new CommandAction($this->app, $command, $method, $this->ui->types, $this->ui->parser));
            }
        }
    }

    private function registerDomainObjects() {
        foreach ($this->findSubClasses(DomainObject::class) as $object) {
            if ($object->hasMethod('created')) {
                $this->addAction($object->getShortName() . '$create', $object->getShortName(),
                    new CommandAction($this->app, 'create', $object->getMethod('created'), $this->ui->types, $this->ui->parser));
            }

            $this->addAction($object->getShortName() . '$read', $object->getShortName(),
                new QueryAction($this->app, $object->getName(), $this->ui->types, $this->ui->parser));

            $this->defineClassIfNotExists($object->getName() . 'List', AggregateList::class);
            $this->addAction($object->getShortName() . '$all', $object->getShortName(),
                new QueryAction($this->app, $object->getName() . 'List', $this->ui->types, $this->ui->parser));
        }
    }

    private function findSubClasses($baseClass) {
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, $baseClass)) {
                yield new \ReflectionClass($class);
            }
        }
    }

    private function findCommandMethods(\ReflectionClass $rootClass) {
        $commandMethods = [];
        foreach ($rootClass->getMethods() as $method) {
            if ($method->getName() != 'handle' && substr($method->getName(), 0, strlen('handle')) == 'handle') {
                $commandMethods[substr($method->getName(), strlen('handle'))] = $method;
            }
        }
        return $commandMethods;
    }

    private function addAction($id, $group, Action $action) {
        $this->ui->actions->add($id, $action);
        $this->ui->groups->put($id, $group);
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
}