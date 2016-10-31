<?php
namespace rtens\proto;

abstract class SingletonAggregateRoot extends AggregateRoot {

    public function __construct() {
        parent::__construct(new GenericAggregateIdentifier(get_class($this), (new \ReflectionClass($this))->getShortName()));
    }
}