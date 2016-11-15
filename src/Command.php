<?php
namespace rtens\udity;

/**
 * Commands are turned into Events by CommandHandlers.
 */
class Command {
    /**
     * @var AggregateIdentifier
     */
    private $aggregateIdentifier;
    /**
     * @var string
     */
    private $name;
    /**
     * @var array
     */
    private $arguments;

    public function __construct(AggregateIdentifier $aggregateIdentifier, $name, array $arguments = []) {
        $this->name = $name;
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->arguments = $arguments;
    }

    /**
     * @return AggregateIdentifier
     */
    public function getAggregateIdentifier() {
        return $this->aggregateIdentifier;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getArguments() {
        return $this->arguments;
    }
}