<?php
namespace rtens\udity;

use rtens\scrut\failures\AssertionFailedFailure;
use rtens\udity\check\DomainSpecification;

abstract class CheckDomainSpecification extends Specification {

    protected function shouldFail(callable $callable, $message) {
        try {
            $this->shouldPass($callable);
        } catch (AssertionFailedFailure $failedFailure) {
            $this->assert($failedFailure->getFailureMessage(), $message);
            return;
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