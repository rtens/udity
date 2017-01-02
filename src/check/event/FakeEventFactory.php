<?php
namespace rtens\udity\check\event;

use rtens\udity\Event;

class FakeEventFactory {
    /**
     * @var string
     */
    private $name;
    /**
     * @var mixed[]
     */
    private $payload = [];
    /**
     * @var string
     */
    private $aggregateClass;
    /**
     * @var string
     */
    private $aggregateKey;

    /**
     * @param string $name
     * @param string $aggregateClass
     * @param string $aggregateKey
     */
    public function __construct($name, $aggregateClass, $aggregateKey) {
        $this->name = $name;
        $this->aggregateClass = $aggregateClass;
        $this->aggregateKey = $aggregateKey;
    }

    public function with($key, $value) {
        $this->payload[$key] = $value;
        return $this;
    }

    public function makeEvent() {
        $identifierClass = $this->aggregateClass . 'Identifier';
        return new Event(new $identifierClass($this->aggregateKey), $this->name, $this->payload);
    }
}