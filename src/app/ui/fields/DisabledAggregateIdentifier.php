<?php
namespace rtens\proto\app\ui\fields;

use rtens\proto\AggregateIdentifier;

class DisabledAggregateIdentifier extends AggregateIdentifier {
    /**
     * @var AggregateIdentifier
     */
    private $identifier;

    /**
     * @param AggregateIdentifier $identifier
     */
    public function __construct(AggregateIdentifier $identifier) {
        parent::__construct($identifier->getKey());
        $this->identifier = $identifier;
    }

    public function getName() {
        return $this->identifier->getName();
    }
}