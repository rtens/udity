<?php
namespace rtens\udity\check\event;

use rtens\udity\check\FailedAssertion;
use rtens\udity\Event;

class MatchedEventsAssertion {
    /**
     * @var Event[]
     */
    private $events;

    /**
     * @param Event[] $events
     */
    public function __construct(array $events) {
        $this->events = $events;
    }

    public function shouldCount($int) {
        if (count($this->events) != $int) {
            throw new FailedAssertion("Expected to match $int events but got " . count($this->events));
        }
    }

    public function shouldBeAppended() {
        if (empty($this->events)) {
            throw new FailedAssertion("Event was not appended");
        }
    }

    public function shouldNotBeAppended() {
        $this->shouldCount(0);
    }

    public function should(callable $condition) {
        $this->shouldBeAppended();

        foreach ($this->events as $event) {
            if (!$condition($event)) {
                throw new FailedAssertion("Not all events match the condition");
            }
        }
    }
}