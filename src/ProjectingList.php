<?php
namespace rtens\proto;

/**
 * Forwards all Events to its items.
 */
abstract class ProjectingList {
    /**
     * @var Projecting[]
     */
    private $items = [];

    /**
     * @param Event $event
     * @return bool
     */
    abstract protected function matchesEvent(Event $event);

    /**
     * @param Event $event
     * @return object
     */
    abstract protected function createInstance(Event $event);

    /**
     * @param Event $event
     * @return void
     */
    public function apply(Event $event) {
        $id = (string)$event->getAggregateIdentifier();

        if (!$this->matchesEvent($event)) {
            return;
        }

        if (!array_key_exists($id, $this->items)) {
            $this->items[$id] = $this->createInstance($event);
        }
        $this->items[$id]->apply($event);
    }

    /**
     * @return array
     */
    public function getAll() {
        return array_values($this->items);
    }
}