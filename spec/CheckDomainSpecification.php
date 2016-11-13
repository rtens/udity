<?php
namespace rtens\proto;

use rtens\proto\check\DomainSpecification;

abstract class CheckDomainSpecification extends Specification {

    protected function shouldFail(callable $callable, $message) {
        try {
            $this->shouldPass($callable);
        } catch (\Exception $exception) {
            $this->assert($exception->getMessage(), $message);
            return;
        }

        throw new \Exception('Did not fail');
    }

    protected function shouldPass(callable $callable) {
        $spec = new DomainSpecification($this->domainClasses);
        $callable($spec);
        $this->assert->pass();
    }
}