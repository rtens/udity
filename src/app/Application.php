<?php
namespace rtens\udity\app;

use rtens\domin\delivery\web\WebApplication;
use rtens\udity\app\ui\WebInterface;
use rtens\udity\Command;
use rtens\udity\domain\command\Aggregate;
use rtens\udity\Projection;
use rtens\udity\Query;
use rtens\udity\utils\ArgumentFiller;
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
     * @param EventStore $eventStore
     */
    public function __construct(EventStore $eventStore) {
        $aggregates = (new GenericAggregateFactory([$this, 'buildAggregate']))
            ->setGetAggregateIdentifierCallback([$this, 'getAggregateIdentifier']);

        $projections = new GenericProjectionFactory([$this, 'buildProjection']);

        $this->karma = (new GenericApplication($eventStore, $aggregates, $projections))
            ->setCommandCondition([$this, 'isCommand']);
    }

    public static function loadClasses($inFolder) {
        $before = get_declared_classes();

        foreach (glob($inFolder . '/*') as $file) {
            if (is_dir($file)) {
                self::loadClasses($file);
            } else if (strtolower(substr($file, -4)) == '.php') {
                require_once $file;
            }
        }

        return array_diff(get_declared_classes(), $before);
    }

    /**
     * @param WebApplication $ui
     * @param string[] $domainClasses
     */
    public function run(WebApplication $ui, array $domainClasses) {
        $generator = new ClassGenerator();
        $webInterface = new WebInterface($this, $ui);

        $webInterface->prepare($generator->inferClasses($domainClasses));
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