<?php
namespace rtens\udity;

use rtens\scrut\failures\AssertionFailedFailure;
use rtens\udity\check\DomainSpecification;

abstract class CheckDomainSpecification extends Specification {

    protected function shouldFail(callable $callable, $message = null, $value = null) {
        try {
            $this->shouldPass($callable, $value);
        } catch (AssertionFailedFailure $failedFailure) {
            return $this->assertMessage($failedFailure->getFailureMessage(), $message);
        } catch (\Exception $exception) {
            return $this->assertMessage($exception->getMessage(), $message);
        }

        throw new \Exception('Did not fail');
    }

    protected function shouldPass(callable $callable, $value = null) {
        $spec = new DomainSpecification($this->domainClasses);
        $callable($spec, $value);
        $this->assert->pass();
    }

    protected function shouldToggle($good, $bad, callable $callable) {
        $this->shouldPass($callable, $good);
        $this->shouldFail($callable, null, $bad);
    }

    private function assertMessage($actual, $message) {
        if (!is_null($message)) {
            $this->assert($actual, $message);
        }
        return;
    }
}