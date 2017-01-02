<?php
namespace rtens\udity\check;

use rtens\domin\delivery\web\WebApplication;
use rtens\udity\AggregateIdentifier;
use rtens\udity\utils\Str;

class FakeAggregate {
    /**
     * @var \ReflectionClass
     */
    private $aggregate;
    /**
     * @var WebApplication
     */
    private $ui;
    /**
     * @var callable|null
     */
    private $catcher;
    /**
     * @var AggregateIdentifier
     */
    private $identifier;

    public function __construct(\ReflectionClass $aggregate, AggregateIdentifier $identifier,
                                WebApplication $ui, callable $catcher = null) {
        $this->aggregate = $aggregate;
        $this->ui = $ui;
        $this->catcher = $catcher;
        $this->identifier = $identifier;
    }

    public function __call($method, $arguments) {
        $command = $method;

        $methodString = Str::g($method);
        if ($methodString->startsWith('handle')) {
            $command = $methodString->after('handle');
        }

        $actionId = $this->aggregate->getShortName() . '$' . $command;

        $action = $this->ui->actions->getAction($actionId);
        $parameters = [
            'target' => $this->identifier
        ];

        try {
            $action->execute($parameters);
        } catch (\Exception $exception) {
            if (is_null($this->catcher)) {
                throw $exception;
            }

            call_user_func($this->catcher, $exception);
        }
    }
}