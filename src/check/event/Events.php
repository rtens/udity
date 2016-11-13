<?php
namespace rtens\proto\check\event;

use rtens\proto\check\event\matchers\AnyEventMatcher;
use rtens\proto\check\event\matchers\NamedEventMatcher;

class Events {

    /**
     * @return AnyEventMatcher
     */
    public static function any() {
        return new AnyEventMatcher();
    }

    public static function named($string) {
        return new NamedEventMatcher($string);
    }
}