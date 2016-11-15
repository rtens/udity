<?php
namespace rtens\udity\check\event;

use rtens\udity\AggregateIdentifier;
use rtens\udity\Event;

class EventFactory {
    /**
     * @var string
     */
    private $name;
    /**
     * @var mixed[]
     */
    private $payload = [];

    /**
     * @param string $name
     */
    public function __construct($name) {
        $this->name = $name;
    }

    public function with($key, $value) {
        $this->payload[$key] = $value;
        return $this;
    }

    public function makeEvent(AggregateIdentifier $identifier) {
        return new Event($identifier, $this->name, $this->payload);
    }
}