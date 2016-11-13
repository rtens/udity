<?php
namespace rtens\proto\check\event\matchers;

use rtens\proto\check\event\EventMatcher;
use rtens\proto\Event;

class AnyEventMatcher extends EventMatcher {

    /**
     * @param Event $event
     * @return bool
     */
    protected function _matches(Event $event) {
        return true;
    }
}