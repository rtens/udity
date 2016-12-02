<?php
namespace rtens\udity\check\event;

use rtens\udity\AggregateIdentifier;
use rtens\udity\Event;

abstract class EventMatcher {
    private $payload = [];
    private $identifierKey;

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
        return
            $this->_matches($event)
            && $this->matchesIdentifierKey($event->getAggregateIdentifier())
            && $this->matchesPayload($event->getPayload());
    }

    public function in($identifierKey) {
        $this->identifierKey = $identifierKey;
        return $this;
    }

    public function with($key, $value) {
        $this->payload[$key] = $value;
        return $this;
    }

    private function matchesPayload(array $payload) {
        foreach ($this->payload as $key => $value) {
            if (!array_key_exists($key, $payload) || $payload[$key] != $value) {
                return false;
            }
        }
        return true;
    }

    private function matchesIdentifierKey(AggregateIdentifier $identifier) {
        return !$this->identifierKey || $this->identifierKey == $identifier->getKey();
    }
}