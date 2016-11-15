<?php
namespace rtens\udity\check\event\matchers;

use rtens\udity\check\event\EventMatcher;
use rtens\udity\Event;

class AnyEventMatcher extends EventMatcher {

    /**
     * @param Event $event
     * @return bool
     */
    protected function _matches(Event $event) {
        return true;
    }
}