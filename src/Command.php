<?php
namespace rtens\proto;

/**
 * Commands are always executed by an AggregateRoot and return nothing
 */
class Command implements Request {
    /**
     * @var string
     */
    private $name;
    /**
     * @var AggregateIdentifier
     */
    private $aggregateIdentifier;
    /**
     * @var array
     */
    private $arguments;

    public function __construct($name, AggregateIdentifier $aggregateIdentifier, array $arguments = []) {
        $this->name = $name;
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->arguments = $arguments;
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
}