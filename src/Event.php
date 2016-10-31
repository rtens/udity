<?php
namespace rtens\proto;

class Event {

    private $name;
    private $aggregateIdentifier;
    private $arguments;
    private $when;

    public function __construct(AggregateIdentifier $aggregateIdentifier, $name, array $arguments = [], \DateTimeImmutable $when = null) {
        $this->name = $name;
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->arguments = $arguments;
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
    public function getArguments() {
        return $this->arguments;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getWhen() {
        return $this->when;
    }
}