<?php
namespace rtens\proto\domain\query;

use rtens\proto\AggregateIdentifier;
use rtens\proto\Event;
use rtens\proto\Projection;
use rtens\proto\utils\ArgumentFiller;

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