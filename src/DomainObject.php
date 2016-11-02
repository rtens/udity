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
        return parent::getIdentifier();
    }

    public function handle(Command $command) {
        if ($command->getName() == 'create') {
            return new Event($command->getAggregateIdentifier(),
                'Created',
                $command->getArguments());
        } else if (substr($command->getName(), 0, 6) == 'change') {
            return new Event($command->getAggregateIdentifier(),
                'Changed',
                [
                    'property' => substr($command->getName(), strlen('change')),
                    'value' => array_values($command->getArguments())[0]
                ]);
        } else if (substr($command->getName(), 0, 2) == 'do') {
            if (method_exists($this, $command->getName())) {
                ArgumentFiller::from($this, $command->getName())
                    ->invoke($this, $command->getArguments());
            }

            return new Event($command->getAggregateIdentifier(),
                'Did' . substr($command->getName(), 2),
                $command->getArguments());
        }

        return [];
    }

    public function apply(Event $event) {
        if ($event->getAggregateIdentifier() != $this->getIdentifier()) {
            return;
        }

        if ($event->getName() == 'Created') {
            ArgumentFiller::from($this, 'created')
                ->inject(Event::class, $event)
                ->invoke($this, $event->getArguments());
        } else if ($event->getName() == 'Changed') {
            ArgumentFiller::from($this, 'set' . $event->getArguments()['property'])
                ->invoke($this, [$event->getArguments()['value']]);
        } else if (substr($event->getName(), 0, 3) == 'Did') {
            ArgumentFiller::from($this, $event->getName())
                ->invoke($this, $event->getArguments());
        }
    }
}