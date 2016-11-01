<?php
namespace rtens\proto;

use rtens\domin\delivery\web\WebApplication;
use watoki\karma\Application as Karma;
use watoki\karma\command\AggregateFactory;
use watoki\karma\implementations\GenericApplication;
use watoki\karma\query\ProjectionFactory;
use watoki\karma\stores\EventStore;

/**
 * Registers all actions and forwards them to karma.
 */
class Application implements AggregateFactory, ProjectionFactory {

    /** @var Karma */
    private $karma;

    public function __construct(EventStore $eventStore) {
        $this->karma = new GenericApplication($eventStore, $this, $this);
        $this->karma->setCommandCondition(function ($request) {
            return $request instanceof Command;
        });
    }

    /**
     * @param WebApplication $ui
     * @return void
     */
    public function run(WebApplication $ui) {
        (new WebInterface($this, $ui))->prepare();
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
        $class = new \ReflectionClass($query->getName());

        $arguments = $query->getArguments();
        if ($class->getConstructor()) {
            $arguments = (new ArgumentFiller($class->getConstructor()))->fill($arguments);
        }

        return $class->newInstanceArgs($arguments);
    }
}