<?php
namespace rtens\proto;

/**
 * AggregateRoot that automatically generates CRUD Commands.
 */
class DomainObject extends AggregateRoot {

    /**
     * @return AggregateIdentifier
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    public function handle(Command $command) {
        if ($command->getName() == 'create') {
            return new Event($command->getAggregateIdentifier(), 'Created', $command->getArguments());
        }

        return [];
    }

    public function apply(Event $event) {
        if ($event->getName() == 'Created') {
            ArgumentFiller::from($this, 'created')
                ->inject(Event::class, $event)
                ->invoke($this, $event->getArguments());
        }
    }
}