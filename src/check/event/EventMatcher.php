<?php
namespace rtens\proto\check\event;

use rtens\proto\Event;

abstract class EventMatcher {
    private $payload = [];

    /**
     * @param Event $event
     * @return bool
     */
    abstract protected function _matches(Event $event);

    /**
     * @param Event $event
     * @return bool
     */
    public function matches(Event $event) {
        return $this->_matches($event) && $this->matchesPayload($event);
    }

    public function with($key, $value) {
        $this->payload[$key] = $value;
        return $this;
    }

    private function matchesPayload(Event $event) {
        $payload = $event->getPayload();
        foreach ($this->payload as $key => $value) {
            if (!array_key_exists($key, $payload) || $payload[$key] != $value) {
                return false;
            }
        }
        return true;
    }
}