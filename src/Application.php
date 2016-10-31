<?php
namespace rtens\proto;

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

        foreach ($this->findSubClasses(AggregateRoot::class) as $root) {
            foreach ($this->findCommandMethods($root) as $command => $method) {
                $domin->actions->add($root->getShortName() . '$' . $command, new CommandAction(
                    $this,
                    $command,
                    $method,
                    $domin->types,
                    $domin->parser
                ));
            }
        }

        foreach ($this->findSubClasses(Projection::class) as $projection) {
            $domin->actions->add($projection->getShortName(), new QueryAction(
                $this,
                $projection->getName(),
                $domin->types,
                $domin->parser
            ));
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
}