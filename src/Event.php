<?php
namespace rtens\proto;

use rtens\proto\utils\Time;

/**
 * Something interesting that happened
 */
class Event {
    /**
     * @var string
     */
    private $name;
    /**
     * @var AggregateIdentifier
     */
    private $aggregateIdentifier;
    /**
     * @var array named
     */
    private $payload;
    /**
     * @var \DateTimeImmutable
     */
    private $when;

    /**
     * @param AggregateIdentifier $aggregateIdentifier
     * @param string $name
     * @param array $payload
     * @param \DateTimeImmutable|null $when defaults to Time::now()
     */
    public function __construct(AggregateIdentifier $aggregateIdentifier, $name, array $payload = [], \DateTimeImmutable $when = null) {
        $this->name = $name;
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->payload = $payload;
        $this->when = $when ?: Time::now();
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return AggregateIdentifier
     */
    public function getAggregateIdentifier() {
        return $this->aggregateIdentifier;
    }

    /**
     * @return array
     */
    public function getPayload() {
        return $this->payload;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getWhen() {
        return $this->when;
    }
}