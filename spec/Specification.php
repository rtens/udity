<?php
namespace spec\rtens\proto;

use rtens\domin\delivery\web\WebApplication;
use rtens\proto\AggregateIdentifier;
use rtens\proto\Application;
use rtens\proto\Event;
use rtens\proto\Time;
use rtens\scrut\Assert;
use watoki\factory\Factory;
use watoki\karma\stores\EventStore;
use watoki\karma\stores\MemoryEventStore;

abstract class Specification {
    /**
     * @var Assert <-
     */
    protected $assert;
    /**
     * @var string
     */
    private $namespace;
    /**
     * @var EventStore
     */
    protected $events;
    /**
     * @var WebApplication
     */
    protected $domin;
    /**
     * @var string[]
     */
    private $knownClasses = [];

    public function before() {
        Time::freeze();
        $this->namespace = uniqid('_');

        $this->events = new MemoryEventStore();
        $this->domin = (new Factory())->getInstance(WebApplication::class);
    }

    protected function assert($value, $equals = true) {
        $this->assert->__invoke($value, $equals);
    }

    protected function runApp() {
        $app = new Application($this->events, $this->knownClasses);
        $app->run($this->domin);
    }

    protected function execute($action, $arguments = []) {
        $this->runApp();
        return $this->domin->actions->getAction($action)->execute($arguments);
    }

    protected function id($aggregate, $key = null) {
        $short = $aggregate . 'Identifier';
        $class = $this->namespace . '\\' . $short;
        if (!class_exists($class)) {
            eval("namespace $this->namespace; 
            class $short extends \\" . AggregateIdentifier::class . " {}");
        }
        return new $class($key ?: $aggregate);
    }

    protected function recordThat($aggregate, $key, $event, $arguments = []) {
        $this->events->append(new Event($this->id($aggregate, $key), $event, $arguments),
            $this->id($aggregate, $key));
    }

    /**
     * @return mixed|Event[]
     */
    protected function recordedEvents() {
        return $this->events->allEvents();
    }

    protected function define($className, $extends, $body = '', $implements = null) {
        $implements = $implements ? ' implements \\' . $implements : '';

        eval("namespace $this->namespace;
        class $className extends \\" . $extends . $implements . " {
            $body
        }");

        $fullName = $this->namespace . '\\' . $className;
        $this->knownClasses[] = $fullName;
        return $fullName;
    }
}