<?php
namespace rtens\udity\check;

use rtens\domin\delivery\web\WebApplication;
use rtens\scrut\Assert;
use rtens\udity\app\Application;
use rtens\udity\check\event\FakeEventFactory;
use rtens\udity\check\event\EventMatcher;
use rtens\udity\check\event\MatchedEventsAssertion;
use rtens\udity\Event;
use rtens\udity\utils\Time;
use watoki\factory\Factory;
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
     * @var FakeEventFactory[][]
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
    /**
     * @var null|Factory
     */
    private $factory;

    public function __construct($domainClasses = null) {
        Time::freeze();
        $this->eventStore = new NosyEventStore(new MemoryEventStore());
        $this->domainClasses = $domainClasses ?: Application::loadClasses('src/domain');
    }

    /**
     * @param $event
     * @param string $aggregateClass
     * @param string $aggregateKey
     * @return FakeEventFactory
     */
    public function givenThat($event, $aggregateClass, $aggregateKey = self::DEFAULT_KEY) {
        $mock = new FakeEventFactory($event, $aggregateClass, $aggregateKey);
        $this->events[$aggregateKey][] = $mock;
        return $mock;
    }

    /**
     * @param string $domainObjectClass
     * @param string $identifierKey
     * @return object
     */
    public function given($domainObjectClass, $identifierKey = self::DEFAULT_KEY) {
        return new FakeDomainObject(
            $domainObjectClass,
            $identifierKey,
            function (FakeEventFactory $eventFactory) use ($identifierKey) {
                $this->events[$identifierKey][] = $eventFactory;
            });
    }

    /**
     * @param string $aggregate
     * @param string $identifierKey
     * @return object
     */
    public function when($aggregate, $identifierKey = self::DEFAULT_KEY) {
        $factory = $this->prepare();
        $identifierClass = $aggregate . 'Identifier';

        return new FakeAggregate(
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

        return new FakeAggregate(
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
        $factory = $this->init();

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

    public function whenProjectObject($domainObjectClass, $key) {
        $factory = $this->prepare();

        $identifierClass = $domainObjectClass . 'Identifier';
        $parameters = [new $identifierClass($key)];

        $this->whenProjectPrepared($domainObjectClass, $parameters, $factory);
    }

    public function whenProject($projectionClass, array $parameters = []) {
        $factory = $this->prepare();
        $this->whenProjectPrepared($projectionClass, $parameters, $factory);
    }

    private function whenProjectPrepared($projectionClass, array $parameters, Factory $factory) {
        $actionId = (new \ReflectionClass($projectionClass))->getShortName();

        /** @var WebApplication $ui */
        $ui = $factory->getInstance(WebApplication::class);
        $this->projection = $ui->actions->getAction($actionId)->execute($parameters);
    }

    /**
     * @return Assert
     */
    public function thenAssert() {
        return new Assert();
    }

    /**
     * @param string $class
     * @return object
     */
    public function projection($class) {
        if (!is_a($this->projection, $class)) {
            throw new FailedAssertion("Projection is not an instance of $class");
        }

        return $this->projection;
    }

    private function init() {
        if ($this->factory === null) {
            $this->factory = WebApplication::init(function (WebApplication $ui) {
                (new Application($this->eventStore))
                    ->run($ui, $this->domainClasses);
            });
        }
        return $this->factory;
    }
}