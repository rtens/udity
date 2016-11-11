<?php
namespace rtens\proto;

use rtens\domin\delivery\web\WebApplication;
use rtens\proto\app\Application;
use rtens\proto\utils\Time;
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
    private $domainClasses = [];

    public function before() {
        Time::freeze();
        $this->namespace = uniqid('_');

        $this->events = new MemoryEventStore();
        $this->domin = (new Factory())->getInstance(WebApplication::class);
    }

    /**
     * @param $value
     * @param bool|mixed|callable $equals
     */
    protected function assert($value, $equals = true) {
        if (is_callable($equals)) {
            $value = $equals($value);
            $equals = true;
        }

        $this->assert->__invoke($value, $equals);
    }

    protected function runApp() {
        $app = new Application($this->events);
        $app->run($this->domin, array_unique($this->domainClasses));
    }

    protected function execute($action, $arguments = []) {
        $this->runApp();
        return $this->action($action)->execute($arguments);
    }

    /**
     * @param string $aggregate
     * @param string|null $key
     * @return AggregateIdentifier
     */
    protected function id($aggregate, $key = null) {
        $short = $aggregate . 'Identifier';
        $class = $this->namespace . '\\' . $short;
        if (!class_exists($class)) {
            eval("namespace $this->namespace; 
            class $short extends \\" . AggregateIdentifier::class . " {}");
        }
        $this->domainClasses[] = $class;
        return new $class($key ?: $aggregate);
    }

    protected function recordThat($aggregate, $key, $event, $arguments = []) {
        $this->events->append(new Event($this->id($aggregate, $key), $event, $arguments),
            $this->id($aggregate, $key)->getKey());
    }

    /**
     * @return mixed|Event[]
     */
    protected function recordedEvents() {
        return $this->events->allEvents();
    }

    protected function define($className, $extends, $body = '', $implements = null) {
        $fullName = $this->fullname($className);

        $this->domainClasses[] = $fullName;
        $this->domainClasses[] = $extends;

        $implementsString = '';
        if ($implements) {
            $this->domainClasses[] = $implements;
            $implementsString = ' implements \\' . $implements;
        }

        $code = "namespace $this->namespace;
            class $className extends \\" . $extends . $implementsString . " {
                $body
            }";
        eval($code) ;

        return $fullName;
    }

    protected function action($action) {
        return $this->domin->actions->getAction($action);
    }

    protected function actionIds() {
        return array_keys($this->domin->actions->getAllActions());
    }

    /**
     * @param $className
     * @return string
     */
    protected function fullname($className) {
        return $this->namespace . '\\' . $className;
    }
}