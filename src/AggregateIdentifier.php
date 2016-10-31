<?php
namespace rtens\proto;

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
        throw new \RuntimeException('Not implement');
    }

    function __toString() {
        return $this->key;
    }
}