<?php
namespace rtens\udity\check;

use rtens\domin\delivery\web\WebApplication;
use rtens\udity\app\Application;
use rtens\udity\check\event\EventFactory;
use rtens\udity\check\event\EventMatcher;
use rtens\udity\check\event\MatchedEventsAssertion;
use rtens\udity\Event;
use rtens\udity\utils\Time;
use watoki\karma\stores\MemoryEventStore;

class DomainSpecification {
    const DEFAULT_KEY = 'key';
    /**
     * @var NosyEventStore
     */
    private $eventStore;
    /**
     * @var string[]
     */
    private $domainClasses;
    /**
     * @var EventFactory[][]
     */
    private $events = [];
    /**
     * @var null|\Exception
     */
    private $caught;

    public function __construct($domainClasses) {
        Time::freeze();
        $this->eventStore = new NosyEventStore(new MemoryEventStore());
        $this->domainClasses = $domainClasses;
    }

    /**
     * @param $event
     * @param string $aggregateKey
     * @return EventFactory
     */
    public function given($event, $aggregateKey = self::DEFAULT_KEY) {
        $mock = new EventFactory($event);
        $this->events[$aggregateKey][] = $mock;
        return $mock;
    }

    /**
     * @param string $aggregate
     * @param string $identifierKey
     * @return object
     */
    public function when($aggregate, $identifierKey = self::DEFAULT_KEY) {
        $factory = $this->prepare($aggregate);
        $identifierClass = $aggregate . 'Identifier';

        return new MockAggregate(
            new \ReflectionClass($aggregate),
            new $identifierClass($identifierKey),
            $factory->getInstance(WebApplication::class));
    }

    /**
     * @param string $aggregate
     * @param string $identifierKey
     * @return object
     */
    public function tryTo($aggregate, $identifierKey = self::DEFAULT_KEY) {
        $factory = $this->prepare($aggregate);
        $identifierClass = $aggregate . 'Identifier';

        return new MockAggregate(
            new \ReflectionClass($aggregate),
            new $identifierClass($identifierKey),
            $factory->getInstance(WebApplication::class),
            function (\Exception $exception) {
                $this->caught = $exception;
            });
    }

    /**
     * @param EventMatcher $matcher
     * @return MatchedEventsAssertion
     */
    public function then(EventMatcher $matcher) {
        return new MatchedEventsAssertion(array_filter($this->eventStore->recordedEvents(),
            function (Event $event) use ($matcher) {
                return $matcher->matches($event);
            }));
    }

    /**
     * @param $aggregate
     * @return \watoki\factory\Factory
     */
    private function prepare($aggregate) {
        $factory = WebApplication::init(function (WebApplication $ui) {
            (new Application($this->eventStore))
                ->run($ui, $this->domainClasses);
        });

        $identifierClass = $aggregate . 'Identifier';
        foreach ($this->events as $key => $events) {
            $identifier = new $identifierClass($key);
            foreach ($events as $event) {
                $this->eventStore->append($event->makeEvent($identifier), $key);
            }
        }

        $this->eventStore->startRecording();

        return $factory;
    }

    public function thenShouldFailWith($message) {
        if (!$this->caught) {
            throw new FailedAssertion('Did not fail');
        } else if ($this->caught->getMessage() != $message) {
            throw new FailedAssertion("Failed with '{$this->caught->getMessage()}' instead of '$message'");
        }
    }
}