<?php
namespace rtens\proto;

/**
 * A singleton has no unique identity.
 */
abstract class SingletonAggregateRoot extends AggregateRoot {

    public function __construct() {
        $identifier = get_class($this) . 'Identifier';
        parent::__construct(new $identifier((new \ReflectionClass($this))->getShortName()));
    }
}