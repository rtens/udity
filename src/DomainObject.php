<?php
namespace rtens\proto;

use watoki\reflect\MethodAnalyzer;

class DomainObject extends AggregateRoot {

    public function handle(Command $command) {
        if ($command->getName() == 'create') {
            $this->invoke('create', $command->getArguments());
            return new Event($command->getAggregateIdentifier(), 'Created', $command->getArguments());
        }

        return [];
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