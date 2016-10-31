<?php
namespace rtens\proto;

use watoki\reflect\MethodAnalyzer;

class Projection implements Projecting {

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