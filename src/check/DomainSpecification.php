<?php
namespace rtens\udity\check;

use rtens\domin\delivery\web\WebApplication;
use rtens\udity\app\Application;
use rtens\udity\check\event\EventFactory;
use rtens\udity\check\event\EventMatcher;
use rtens\udity\check\event\MatchedEventsAssertion;
use rtens\udity\check\projection\MockProjection;
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
    /**
     * @var null|object
     */
    private $projection;

    public function __construct($domainClasses) {
        Time::freeze();
        $this->eventStore = new NosyEventStore(new MemoryEventStore());
        $this->domainClasses = $domainClasses;
    }

    /**
     * @param $event
     * @param string $aggregateClass
     * @param string $aggregateKey
     * @return EventFactory
     */
    public function given($event, $aggregateClass, $aggregateKey = self::DEFAULT_KEY) {
        $mock = new EventFactory($event, $aggregateClass, $aggregateKey);
        $this->events[$aggregateKey][] = $mock;
        return $mock;
    }

    /**
     * @param string $aggregate
     * @param string $identifierKey
     * @return object
     */
    public function when($aggregate, $identifierKey = self::DEFAULT_KEY) {
        $factory = $this->prepare();
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
        $factory = $this->prepare();
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
     * @return \watoki\factory\Factory
     */
    private function prepare() {
        $factory = WebApplication::init(function (WebApplication $ui) {
            (new Application($this->eventStore))
                ->run($ui, $this->domainClasses);
        });

        foreach ($this->events as $key => $events) {
            foreach ($events as $event) {
                $this->eventStore->append($event->makeEvent(), $key);
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

    public function whenProject($projectionClass) {
        $factory = $this->prepare();

        $actionId = (new \ReflectionClass($projectionClass))->getShortName();

        /** @var WebApplication $ui */
        $ui = $factory->getInstance(WebApplication::class);
        $this->projection = $ui->actions->getAction($actionId)->execute([]);
    }

    /**
     * @param string $projectionClass
     * @return object|MockProjection
     */
    public function thenProjected($projectionClass) {
        if (!is_a($this->projection, $projectionClass)) {
            throw new FailedAssertion("Projection is not an instance of $projectionClass");
        }
        return new MockProjection($this->projection);
    }
}