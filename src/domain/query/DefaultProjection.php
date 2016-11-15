<?php
namespace rtens\udity\domain\query;

use rtens\udity\AggregateIdentifier;
use rtens\udity\Event;
use rtens\udity\Projection;
use rtens\udity\utils\ArgumentFiller;

class DefaultProjection implements Projection {

    /**
     * @param Event $event
     * @return void
     */
    public function apply(Event $event) {
        $this->invoke($event, 'apply' . $event->getName());

        $aggregateClass = $event->getAggregateIdentifier()->getName();
        if (class_exists($aggregateClass)) {
            $aggregate = (new \ReflectionClass($aggregateClass))->getShortName();
            $this->invoke($event, 'for' . $aggregate . 'apply' . $event->getName());
        }
    }

    private function invoke(Event $event, $method) {
        if (!method_exists($this, $method)) {
            return;
        }

        ArgumentFiller::from($this, $method)
            ->inject(Event::class, $event)
            ->inject(AggregateIdentifier::class, $event->getAggregateIdentifier())
            ->invoke($this, $event->getPayload());
    }
}