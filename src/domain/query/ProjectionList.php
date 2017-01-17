<?php
namespace rtens\udity\domain\query;

use rtens\udity\Event;
use rtens\udity\Projection;

/**
 * Forwards Events to its Projection items
 */
abstract class ProjectionList extends DefaultProjection {
    /**
     * @var Projection[]
     */
    private $items = [];

    /**
     * @param Event $event
     * @return bool
     */
    abstract protected function matchesEvent(Event $event);

    /**
     * @param Event $event
     * @return Projection
     */
    abstract protected function createItem(Event $event);

    /**
     * @param Event $event
     * @return void
     */
    public function apply(Event $event) {
        $key = $event->getAggregateIdentifier()->getKey();

        if (!$this->matchesEvent($event)) {
            return;
        }

        if (!array_key_exists($key, $this->items)) {
            $this->items[$key] = $this->createItem($event);
        }
        $this->items[$key]->apply($event);

        parent::apply($event);
    }

    /**
     * @return Projection[]
     */
    public function getList() {
        return array_values($this->items);
    }

    /**
     * @return Projection[] indexed by key
     */
    protected function getItems() {
        return $this->items;
    }
}