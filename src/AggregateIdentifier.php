<?php
namespace rtens\udity;

use rtens\udity\utils\Str;

/**
 * Identifies an aggregate by its name and a unique key.
 *
 * The Aggregate name is inferred by the class name.
 * e.g. the class \foo\bat\BazIdentifier identifies the Aggregate \foo\bar\Baz
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
    public function getName() {
        return Str::g(get_class($this))->before('Identifier');
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }
}