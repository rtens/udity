<?php
namespace rtens\proto;

abstract class DomainObjectList {

    /**
     * @var DomainObject[]
     */
    private $objects = [];

    public function apply(Event $event) {
        if ($event->getAggregateIdentifier()->getAggregateName() != $this->aggregate()->getName()) {
            return;
        }

        $id = (string)$event->getAggregateIdentifier();
        if (!array_key_exists($id, $this->objects)) {
            $this->objects[$id] = $this->aggregate()->newInstance($event->getAggregateIdentifier());
        }
        $this->objects[$id]->apply($event);
    }

    public function getAll() {
        return array_values($this->objects);
    }

    private function aggregate() {
        return new \ReflectionClass(substr(get_class($this), 0, -strlen('List')));
    }
}