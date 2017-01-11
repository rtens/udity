<?php
namespace rtens\udity\check\event;

use rtens\udity\Event;
use rtens\udity\utils\Time;

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
     * @var \DateTimeImmutable
     */
    private $when;

    /**
     * @param string $name
     * @param string $aggregateClass
     * @param string $aggregateKey
     */
    public function __construct($name, $aggregateClass, $aggregateKey) {
        $this->name = $name;
        $this->aggregateClass = $aggregateClass;
        $this->aggregateKey = $aggregateKey;
        $this->when = Time::now();
    }

    public function with($key, $value) {
        $this->payload[$key] = $value;
        return $this;
    }

    public function at($timeString) {
        $this->when = Time::at($timeString);
    }

    public function makeEvent() {
        $identifierClass = $this->aggregateClass . 'Identifier';
        return new Event(new $identifierClass($this->aggregateKey), $this->name, $this->payload, $this->when);
    }
}