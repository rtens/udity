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
        return Str::g(get_class($this))->before('Identifier');
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }
}