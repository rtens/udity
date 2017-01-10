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

    public function handle(Command $command) {
        $commandName = Str::g($command->getName());

        if ($commandName->is('create')) {
            return $this->that($command, 'Created');
        } else if ($commandName->startsWith('change')) {
            return $this->that($command, 'Changed' . $commandName->after('change'));
        } else if ($commandName->startsWith('do')) {
            if (method_exists($this, $command->getName())) {
                $argumentFiller = ArgumentFiller::from($this, $command->getName());
                $argumentFiller->invoke($this, $command->getArguments());

                $command = new Command(
                    $command->getAggregateIdentifier(),
                    $command->getName(),
                    $argumentFiller->fill($command->getArguments()));
            }
            return $this->that($command, 'Did' . $commandName->after('do'));
        } else {
            return parent::handle($command);
        }
    }

    private function that(Command $command, $event) {
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