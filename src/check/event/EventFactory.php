<?php
namespace rtens\proto\check\event;

use rtens\proto\AggregateIdentifier;
use rtens\proto\Event;

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