<?php
namespace rtens\proto\domain\command;

use rtens\proto\AggregateIdentifier;

/**
 * An Aggregate without identity.
 *
 * The key of a Singleton's identifier is the its short class name
 */
abstract class Singleton extends Aggregate {

    public function __construct() {
        parent::__construct($this->buildIdentifier());
    }

    /**
     * @return AggregateIdentifier
     */
    protected function buildIdentifier() {
        $identifierClass = get_class($this) . 'Identifier';
        return new $identifierClass((new \ReflectionClass($this))->getShortName());
    }
}