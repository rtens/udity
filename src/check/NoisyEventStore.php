<?php
namespace rtens\udity\check;

use watoki\karma\stores\EventStore;

class NoisyEventStore implements EventStore {
    /**
     * @var EventStore
     */
    private $store;
    /**
     * @var EventStore[]
     */
    private $recordedEvents = [];

    /**
     * @param EventStore $store
     */
    public function __construct($store) {
        $this->store = $store;
    }

    /**
     * @return mixed[] All events appended to any aggregate
     */
    public function allEvents() {
        return $this->store->allEvents();
    }

    /**
     * @param mixed $aggregateIdentifier
     * @return mixed[] Events appended to aggregate
     */
    public function eventsOf($aggregateIdentifier) {
        return $this->store->eventsOf($aggregateIdentifier);
    }

    /**
     * @param mixed $event
     * @param mixed $aggregateIdentifier
     * @return void
     */
    public function append($event, $aggregateIdentifier) {
        $this->store->append($event, $aggregateIdentifier);
        $this->recordedEvents[] = $event;
    }

    public function startRecording() {
        $this->recordedEvents = [];
    }

    public function recordedEvents() {
        return $this->recordedEvents;
    }
}