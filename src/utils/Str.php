<?php
namespace rtens\proto\utils;

class Str {
    /**
     * @var string
     */
    private $string;

    /**
     * @param string $string
     */
    public function __construct($string) {
        $this->string = (string)$string;
    }

    public static function g($string) {
        return new Str($string);
    }

    /**
     * @return string
     */
    public function getString() {
        return $this->string;
    }

    /**
     * @param string $string
     */
    public function setString($string) {
        $this->string = $string;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->string;
    }

    public function startsWith($prefix) {
        return substr($this->string, 0, strlen($prefix)) == $prefix;
    }

    public function startsWithButIsNot($prefix) {
        return $this->startsWith($prefix) && !$this->is($prefix);
    }

    public function endsWith($suffix) {
        return substr($this->string, -strlen($suffix)) == $suffix;
    }

    public function after($prefix) {
        return substr($this->string, strlen($prefix));
    }

    public function before($suffix) {
        return substr($this->string, 0, -strlen($suffix));
    }

    public function is($string) {
        return $this->string === $string;
    }
}