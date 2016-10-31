<?php
namespace rtens\proto;

use watoki\reflect\MethodAnalyzer;

abstract class AggregateRoot {

    /**
     * @var AggregateIdentifier
     */
    protected $identifier;
    /**
     * @var Event[]
     */
    private $recordedEvents = [];

    public function __construct(AggregateIdentifier $identifier) {
        $this->identifier = $identifier;
    }

    protected function recordThat($eventName, array $arguments = []) {
        $this->recordedEvents[] = new Event($this->identifier, $eventName, $arguments);
    }

    /**
     * @param Command $command
     * @return Event[]
     * @throws \Exception
     */
    public function handle(Command $command) {
        $method = 'handle' . $command->getName();
        if (!method_exists($this, $method)) {
            throw new \Exception("Missing method " . get_class($this) . '::' . $method . '()');
        }
        $this->invoke($method, $command->getArguments());
        return $this->recordedEvents;
    }

    /**
     * @param Event $event
     * @return void
     */
    public function apply(Event $event) {
        $method = 'apply' . $event->getName();
        if (!method_exists($this, $method)) {
            return;
        }
        $this->invoke($method, $event->getArguments(), [
            Event::class => $event
        ]);
    }

    private function invoke($method, $arguments, $injected = []) {
        $injector = function ($class) use ($injected) {
            if (array_key_exists($class, $injected)) {
                return $injected[$class];
            }
            return null;
        };
        $filter = function () {
            return true;
        };

        $analyzer = new MethodAnalyzer(new \ReflectionMethod($this, $method));
        $arguments = $analyzer->fillParameters($arguments, $injector, $filter);

        call_user_func_array([$this, $method], $arguments);
    }
}