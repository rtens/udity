<?php
namespace rtens\proto;

/**
 * Identifies an aggregate by its name and a unique key
 */
abstract class AggregateIdentifier {
    /**
     * @var string
     */
    private $key;

    /**
     * @param string $key
     */
    public function __construct($key) {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getAggregateName() {
        return substr(get_class($this), 0, -strlen('Identifier'));
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    function __toString() {
        return $this->key;
    }
}