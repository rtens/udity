<?php
namespace rtens\proto\domain\command;

use rtens\proto\AggregateIdentifier;
use rtens\proto\Command;
use rtens\proto\CommandHandler;
use rtens\proto\Event;
use rtens\proto\utils\ArgumentFiller;

abstract class Aggregate implements CommandHandler {
    /**
     * @var AggregateIdentifier
     */
    private $identifier;
    /**
     * @var Event[]
     */
    private $recordedEvents = [];

    /**
     * @param AggregateIdentifier $identifier
     */
    public function __construct(AggregateIdentifier $identifier) {
        $this->identifier = $identifier;
    }

    /**
     * @return AggregateIdentifier
     */
    protected function getIdentifier() {
        return $this->identifier;
    }

    /**
     * @param string $eventName
     * @param array $payload
     * @return void
     */
    protected function recordThat($eventName, array $payload = []) {
        $this->recordedEvents[] = new Event($this->identifier, $eventName, $payload);
    }

    /**
     * @param Command $command
     * @return Event[]
     */
    public function handle(Command $command) {
        $method = 'handle' . $command->getName();
        if (!method_exists($this, $method)) {
            throw new \RuntimeException("Missing method " . get_class($this) . '::' . $method . '()');
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
            ->invoke($this, $event->getPayload());
    }
}