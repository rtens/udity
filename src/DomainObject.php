<?php
namespace rtens\proto;

use watoki\reflect\MethodAnalyzer;

class DomainObject extends AggregateRoot {

    /**
     * @return AggregateIdentifier
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    public function handle(Command $command) {
        if ($command->getName() == 'create') {
            return new Event($command->getAggregateIdentifier(), 'Created', $command->getArguments());
        }

        return [];
    }

    public function apply(Event $event) {
        if ($event->getName() == 'Created') {
            $this->invoke('created', $event->getArguments(), [
                Event::class => $event
            ]);
        }
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