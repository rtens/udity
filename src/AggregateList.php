<?php
namespace rtens\proto;

/**
 * Infers the class of its items by its own name.
 */
abstract class AggregateList extends ProjectingList {

    protected function matchesEvent(Event $event) {
        return $event->getAggregateIdentifier()->getAggregateName() == $this->aggregateClass()->getName();
    }

    protected function createInstance(Event $event) {
        return $this->aggregateClass()->newInstance($event->getAggregateIdentifier());
    }

    private function aggregateClass() {
        return new \ReflectionClass(substr(get_class($this), 0, -strlen('List')));
    }
}