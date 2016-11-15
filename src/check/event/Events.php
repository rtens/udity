<?php
namespace rtens\udity\check\event;

use rtens\udity\check\event\matchers\AnyEventMatcher;
use rtens\udity\check\event\matchers\NamedEventMatcher;

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