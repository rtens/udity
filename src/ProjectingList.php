<?php
namespace rtens\proto;

/**
 * Forwards all Events to its items.
 */
abstract class ProjectingList implements Options {
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
     * @return mixed
     */
    abstract protected function createItem(Event $event);

    /**
     * @param Event $event
     * @return void
     */
    public function apply(Event $event) {
        $id = $event->getAggregateIdentifier()->getKey();

        if (!$this->matchesEvent($event)) {
            return;
        }

        if (!array_key_exists($id, $this->items)) {
            $this->items[$id] = $this->createItem($event);
        }
        $this->items[$id]->apply($event);
    }

    /**
     * @return array
     */
    public function getAll() {
        return array_values($this->items);
    }

    public function options() {
        $options = [];
        foreach ($this->items as $key => $item) {
            $options[$key] = $this->caption($item) ?: $key;
        }
        return $options;
    }

    protected function caption($item) {
        if (method_exists($item, 'caption')) {
            return $item->caption();
        }
        return null;
    }
}