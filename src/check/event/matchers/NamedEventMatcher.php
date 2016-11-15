<?php
namespace rtens\udity\check\event\matchers;

use rtens\udity\check\event\EventMatcher;
use rtens\udity\Event;

class NamedEventMatcher extends EventMatcher {
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * @param Event $event
     * @return bool
     */
    protected function _matches(Event $event) {
        return $event->getName() == $this->name;
    }
}