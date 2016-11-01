<?php
namespace rtens\proto;

/**
 * Root of all the objects of an aggregate.
 *
 * An aggregate protects invariants and consistency of its objects. All changes to any object in an aggregate
 * must go through the root, hence only the root must be referenced from outside the aggregate.
 */
abstract class AggregateRoot {
    /**
     * @var AggregateIdentifier
     */
    protected $identifier;
    /**
     * @var Event[]
     */
    private $recordedEvents = [];

    public function __construct(AggregateIdentifier $identifier) {
        $this->identifier = $identifier;
    }

    /**
     * @param string $eventName
     * @param array $arguments
     */
    protected function recordThat($eventName, array $arguments = []) {
        $this->recordedEvents[] = new Event($this->identifier, $eventName, $arguments);
    }

    /**
     * @param Command $command
     * @return Event[]
     * @throws \Exception
     */
    public function handle(Command $command) {
        $method = 'handle' . $command->getName();
        if (!method_exists($this, $method)) {
            throw new \Exception("Missing method " . get_class($this) . '::' . $method . '()');
        }

        ArgumentFiller::from($this, $method)
            ->invoke($this, $command->getArguments());

        return $this->recordedEvents;
    }

    /**
     * @param Event $event
     * @return void
     */
    public function apply(Event $event) {
        $method = 'apply' . $event->getName();
        if (!method_exists($this, $method)) {
            return;
        }

        ArgumentFiller::from($this, $method)
            ->inject(Event::class, $event)
            ->invoke($this, $event->getArguments());
    }
}