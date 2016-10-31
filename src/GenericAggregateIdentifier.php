<?php
namespace rtens\proto;

class GenericAggregateIdentifier extends AggregateIdentifier {
    private $name;
    private $key;

    /**
     * @param string $name
     * @param string $key
     */
    public function __construct($name, $key) {
        parent::__construct($key);
        $this->name = $name;
        $this->key = $key;
    }

    public function getAggregateName() {
        return $this->name;
    }
}