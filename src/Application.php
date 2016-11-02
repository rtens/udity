<?php
namespace rtens\proto;

use rtens\domin\delivery\web\WebApplication;
use watoki\karma\Application as Karma;
use watoki\karma\implementations\aggregates\GenericAggregateFactory;
use watoki\karma\implementations\GenericApplication;
use watoki\karma\implementations\projections\GenericProjectionFactory;
use watoki\karma\stores\EventStore;

/**
 * Registers all actions and forwards them to karma.
 */
class Application {
    /**
     * @var Karma
     */
    private $karma;
    /**
     * @var string[]
     */
    private $knownClasses;

    public function __construct(EventStore $eventStore, array $knownClasses = null) {
        $this->knownClasses = is_null($knownClasses)
            ? get_declared_classes()
            : $knownClasses;

        $aggregates = (new GenericAggregateFactory([$this, 'buildAggregateRoot']))
            ->setGetAggregateIdentifierCallback([$this, 'getAggregateIdentifier']);

        $projections = new GenericProjectionFactory([$this, 'buildProjection']);

        $this->karma = (new GenericApplication($eventStore, $aggregates, $projections))
            ->setCommandCondition([$this, 'isCommand']);
    }

    /**
     * @param WebApplication $ui
     * @return void
     */
    public function run(WebApplication $ui) {
        (new WebInterface($this, $ui, $this->knownClasses))->prepare();
    }

    /**
     * @param Request $request
     * @return mixed response
     */
    public function handle(Request $request) {
        return $this->karma->handle($request);
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isCommand(Request $request) {
        return $request instanceof Command;
    }

    /**
     * @param Command $command
     * @return AggregateIdentifier
     */
    public function getAggregateIdentifier(Command $command) {
        return $command->getAggregateIdentifier();
    }

    /**
     * @param Command $command
     * @return object|AggregateRoot
     */
    public function buildAggregateRoot(Command $command) {
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