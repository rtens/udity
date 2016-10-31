<?php
namespace rtens\proto;

use rtens\domin\Action;
use rtens\domin\delivery\web\WebApplication;
use watoki\karma\Application as Karma;
use watoki\karma\command\AggregateFactory;
use watoki\karma\implementations\GenericApplication;
use watoki\karma\query\ProjectionFactory;
use watoki\karma\stores\EventStore;
use watoki\reflect\MethodAnalyzer;

class Application implements AggregateFactory, ProjectionFactory {

    /** @var Karma */
    private $karma;

    public function __construct(EventStore $eventStore) {
        $this->karma = new GenericApplication($eventStore, $this, $this);
        $this->karma->setCommandCondition(function ($request) {
            return $request instanceof Command;
        });
    }

    public function run(WebApplication $domin) {
        $domin->types = new DefaultTypeFactory();

        foreach ($this->findSubClasses(Projecting::class) as $projection) {
            $this->addAction($domin, $projection->getShortName(), 'Show',
                new QueryAction($this, $projection->getName(), $domin->types, $domin->parser));
        }

        foreach ($this->findSubClasses(AggregateRoot::class) as $root) {
            if (!class_exists($root->getName() . 'Identifier')) {
                $parts = explode('\\', $root->getName());
                $shortName = array_pop($parts) . 'Identifier';
                $nameSpace = implode('\\', $parts);

                eval("namespace $nameSpace; class $shortName extends \\" . AggregateIdentifier::class . " {}");
            }
            foreach ($this->findCommandMethods($root) as $command => $method) {
                $this->addAction($domin, $root->getShortName() . '$' . $command, $root->getShortName(),
                    new CommandAction($this, $command, $method, $domin->types, $domin->parser));
            }
        }

        foreach ($this->findSubClasses(DomainObject::class) as $object) {
            if ($object->hasMethod('created')) {
                $this->addAction($domin, $object->getShortName() . '$create', $object->getShortName(),
                    new CommandAction($this, 'create', $object->getMethod('created'), $domin->types, $domin->parser));
            }
            $this->addAction($domin, $object->getShortName() . '$read', $object->getShortName(),
                new QueryAction($this, $object->getName(), $domin->types, $domin->parser));

            $listClass = $object->getName() . 'List';
            if (!class_exists($listClass)) {
                $parts = explode('\\', $listClass);
                $shortName = array_pop($parts);
                $nameSpace = implode('\\', $parts);

                eval("namespace $nameSpace; class $shortName extends \\" . DomainObjectList::class . " {}");
            }

            $this->addAction($domin, $object->getShortName() . '$all', $object->getShortName(),
                new QueryAction($this, $listClass, $domin->types, $domin->parser));
        }
    }

    /**
     * @param string $baseClass
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

    /**
     * @param Request $request
     * @return mixed response
     */
    public function handle(Request $request) {
        return $this->karma->handle($request);
    }

    /**
     * @param mixed $command
     * @return string
     */
    public function handleMethod($command) {
        return 'handle';
    }

    /**
     * @param mixed $event
     * @return string
     */
    public function applyMethod($event) {
        return 'apply';
    }

    /**
     * @param Command $command
     * @return AggregateIdentifier
     */
    public function getAggregateIdentifier($command) {
        return $command->getAggregateIdentifier();
    }

    /**
     * @param Command $command
     * @return object|AggregateRoot
     */
    public function buildAggregateRoot($command) {
        $class = new \ReflectionClass($command->getAggregateIdentifier()->getAggregateName());

        if ($class->isSubclassOf(DomainObject::class)) {
            return $class->newInstanceArgs(array_merge([$command->getAggregateIdentifier()], $command->getArguments()));
        }

        return $class->newInstance($command->getAggregateIdentifier());
    }

    /**
     * @param Query $query
     * @return object|Projection
     */
    public function buildProjection($query = null) {
        $injector = function () {
        };
        $filter = function () {
            return true;
        };

        $class = new \ReflectionClass($query->getName());
        $arguments = $query->getArguments();

        if ($class->getConstructor()) {
            $analyzer = new MethodAnalyzer($class->getConstructor());
            $arguments = $analyzer->fillParameters($arguments, $injector, $filter);
        }

        return $class->newInstanceArgs($arguments);
    }

    private function addAction(WebApplication $domin, $id, $group, Action $action) {
        $domin->actions->add($id, $action);
        $domin->groups->put($id, $group);
    }
}