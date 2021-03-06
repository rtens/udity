<?php
namespace rtens\udity;

use rtens\domin\delivery\web\WebApplication;
use rtens\scrut\Assert;
use rtens\udity\app\Application;
use rtens\udity\utils\Time;
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
    protected $domainClasses = [];

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
        $fullName = $this->fqn($className);

        $extendsClass = new \ReflectionClass($extends);
        if ($extendsClass->isInterface()) {
            $implements = $extends;
            $extends = \stdClass::class;
        }

        $this->domainClasses[] = $fullName;
        $this->domainClasses[] = $extends;

        $extendsParent = $extendsClass->getParentClass();
        while ($extendsParent) {
            $this->domainClasses[] = $extendsParent->getName();
            $extendsParent = $extendsParent->getParentClass();
        }

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
    protected function fqn($className) {
        return $this->namespace . '\\' . $className;
    }
}