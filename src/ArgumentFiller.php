<?php
namespace rtens\proto;

use watoki\reflect\MethodAnalyzer;

class ArgumentFiller {
    /**
     * @var \ReflectionMethod
     */
    private $method;
    /**
     * @var object[]
     */
    private $injections = [];

    public function __construct(\ReflectionMethod $method) {
        $this->method = $method;
    }

    /**
     * @param string $class
     * @param string $methodName
     * @return ArgumentFiller
     */
    public static function from($class, $methodName) {
        return new ArgumentFiller(new \ReflectionMethod($class, $methodName));
    }

    /**
     * @param string $class
     * @param object $object
     * @return static
     */
    public function inject($class, $object) {
        $this->injections[$class] = $object;
        return $this;
    }

    /**
     * @param object $object
     * @param array $arguments
     * @return mixed
     */
    public function invoke($object, array $arguments) {
        return $this->method->invokeArgs($object, $this->fill($arguments));
    }

    /**
     * @param array $arguments
     * @return array
     */
    public function fill(array $arguments) {
        $injector = function ($class) {
            if (array_key_exists($class, $this->injections)) {
                return $this->injections[$class];
            }
            return null;
        };
        $filter = function () {
            return true;
        };

        $analyzer = new MethodAnalyzer($this->method);
        return $analyzer->fillParameters($arguments, $injector, $filter);
    }
}