<?php
namespace rtens\udity\check;

use rtens\udity\check\event\FakeEventFactory;
use rtens\udity\utils\Str;

class FakeDomainObject {
    /**
     * @var string
     */
    private $domainObject;
    /**
     * @var string
     */
    private $identifierKey;
    /**
     * @var callable
     */
    private $setter;

    public function __construct($domainObjectClass, $identifierKey, callable $setter) {
        $this->domainObject = $domainObjectClass;
        $this->identifierKey = $identifierKey;
        $this->setter = $setter;
    }

    public function __call($method, $arguments) {
        $methodString = Str::g($method);
        if ($methodString->startsWith('do')) {
            $method = 'did' . $methodString->after('do');
        }
        $mock = new FakeEventFactory(ucfirst($method), $this->domainObject, $this->identifierKey);

        $reflectionMethod = new \ReflectionMethod($this->domainObject, $method);
        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            if (array_key_exists($i, $arguments)) {
                $mock->with($parameter->getName(), $arguments[$i]);
            }
        }
        call_user_func($this->setter, $mock);
    }
}