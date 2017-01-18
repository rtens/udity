<?php
namespace rtens\udity\domain\objects;

use rtens\udity\domain\command\Aggregate;
use rtens\udity\AggregateIdentifier;
use rtens\udity\Command;
use rtens\udity\Event;
use rtens\udity\Projection;
use rtens\udity\utils\ArgumentFiller;
use rtens\udity\utils\Str;

abstract class DomainObject extends Aggregate implements Projection {
    /**
     * @var Event[]
     */
    private $recordedEvents = [];

    /**
     * @return AggregateIdentifier
     */
    public function getIdentifier() {
        return parent::getIdentifier();
    }

    /**
     * @return string
     */
    public function caption() {
        return $this->getIdentifier()->getKey();
    }

    protected function recordThat($eventName, array $payload = []) {
        $this->recordedEvents[] = new Event($this->getIdentifier(), $eventName, $payload);
    }

    public function handle(Command $command) {
        $commandName = Str::g($command->getName());

        if ($commandName->is('create')) {
            return $this->executeCommand($command, 'Created');
        } else if ($commandName->startsWith('change')) {
            $property = $commandName->after('change');
            return $this->executeCommand($command, 'Changed' . $property, 'set' . $property);
        } else if ($commandName->startsWith('do')) {
            return $this->executeCommand($command, 'Did' . $commandName->after('do'));
        } else {
            return parent::handle($command);
        }
    }

    private function executeCommand(Command $command, $eventName, $methodName = null) {
        $methodName = $methodName ?: $command->getName();

        if (method_exists($this, $methodName)) {
            $argumentFiller = ArgumentFiller::from($this, $methodName);
            $argumentFiller->invoke($this, $command->getArguments());

            $command = new Command(
                $command->getAggregateIdentifier(),
                $command->getName(),
                $argumentFiller->fill($command->getArguments()));
        }
        return $this->that($command, $eventName);
    }

    private function that(Command $command, $event) {
        if ($this->recordedEvents) {
            return $this->recordedEvents;
        }

        return [
            new Event($command->getAggregateIdentifier(), $event, $command->getArguments())
        ];
    }

    public function apply(Event $event) {
        if ($event->getAggregateIdentifier() != $this->getIdentifier()) {
            return;
        }

        $eventName = Str::g($event->getName());

        if ($eventName->is('Created')) {
            $this->applyEvent($event, 'created');
        } else if ($eventName->startsWith('Changed')) {
            $this->applyEvent($event, 'set' . $eventName->after('Changed'));
        } else if ($eventName->startsWith('Did')) {
            $this->applyEvent($event, $eventName);
        } else {
            parent::apply($event);
        }
    }

    private function applyEvent(Event $event, $methodName) {
        if (!method_exists($this, $methodName)) {
            return;
        }

        ArgumentFiller::from($this, $methodName)
            ->inject(Event::class, $event)
            ->invoke($this, $event->getPayload());
    }
}