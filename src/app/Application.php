<?php
namespace rtens\proto\app;

use rtens\domin\delivery\web\WebApplication;
use rtens\proto\Command;
use rtens\proto\domain\command\Aggregate;
use rtens\proto\Projection;
use rtens\proto\Query;
use rtens\proto\utils\ArgumentFiller;
use watoki\karma\Application as Karma;
use watoki\karma\implementations\aggregates\GenericAggregateFactory;
use watoki\karma\implementations\GenericApplication;
use watoki\karma\implementations\projections\GenericProjectionFactory;
use watoki\karma\stores\EventStore;

/**
 * Maps Commands to CommandHandlers and Queries to Projections
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

        $aggregates = (new GenericAggregateFactory([$this, 'buildAggregate']))
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
    }

    /**
     * @param Command $command
     */
    public function handle(Command $command) {
        $this->karma->handle($command);
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function execute(Query $query) {
        return $this->karma->handle($query);
    }

    /**
     * @param object $maybeCommand
     * @return bool
     */
    public function isCommand($maybeCommand) {
        return $maybeCommand instanceof Command;
    }

    /**
     * @param Command $command
     * @return string
     */
    public function getAggregateIdentifier(Command $command) {
        return $command->getAggregateIdentifier()->getKey();
    }

    /**
     * @param Command $command
     * @return Aggregate
     */
    public function buildAggregate(Command $command) {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return (new \ReflectionClass($command->getAggregateIdentifier()->getName()))
            ->newInstance($command->getAggregateIdentifier());
    }

    /**
     * @param Query $query
     * @return Projection
     */
    public function buildProjection($query = null) {
        $class = new \ReflectionClass($query->getName());
        $constructor = $class->getConstructor();

        if (!$constructor) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $class->newInstance();
        }

        $filledArguments = (new ArgumentFiller($constructor))
            ->fill($query->getArguments());

        return $class->newInstanceArgs($filledArguments);
    }
}