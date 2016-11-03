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
        $commandName = Str::g($command->getName());

        if ($commandName->is('create')) {
            return new Event($command->getAggregateIdentifier(),
                'Created',
                $command->getArguments());
        } else {
            if ($commandName->startsWith('change')) {
                return new Event($command->getAggregateIdentifier(),
                    'Changed' . $commandName->after('change'),
                    $command->getArguments());
            } else if ($commandName->startsWith('do')) {
                if (method_exists($this, $command->getName())) {
                    ArgumentFiller::from($this, $command->getName())
                        ->invoke($this, $command->getArguments());
                }

                return new Event($command->getAggregateIdentifier(),
                    'Did' . $commandName->after('do'),
                    $command->getArguments());
            }
        }

        return [];
    }

    public function apply(Event $event) {
        if ($event->getAggregateIdentifier() != $this->getIdentifier()) {
            return;
        }

        $eventName = Str::g($event->getName());

        if ($eventName->is('Created')) {
            $this->invoke($event, 'created');
        } else if ($eventName->startsWith('Changed')) {
            $this->invoke($event, 'set' . $eventName->after('Changed'));
        } else if ($eventName->startsWith('Did')) {
            $this->invoke($event, $eventName);
        }
    }

    private function invoke(Event $event, $methodName) {
        if (!method_exists($this, $methodName)) {
            return;
        }

        ArgumentFiller::from($this, $methodName)
            ->inject(Event::class, $event)
            ->invoke($this, $event->getArguments());
    }
}