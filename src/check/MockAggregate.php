<?php
namespace rtens\proto\check;

use rtens\domin\delivery\web\WebApplication;
use rtens\proto\utils\Str;

class MockAggregate {
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

    public function __construct(\ReflectionClass $aggregate, WebApplication $ui, callable $catcher = null) {
        $this->aggregate = $aggregate;
        $this->ui = $ui;
        $this->catcher = $catcher;
    }

    public function __call($method, $arguments) {
        $command = Str::g($method)->after('handle');
        $actionId = $this->aggregate->getShortName() . '$' . $command;

        $identifierClass = $this->aggregate->getName() . 'Identifier';

        $action = $this->ui->actions->getAction($actionId);
        $parameters = [
            'target' => new $identifierClass(DomainSpecification::DEFAULT_KEY)
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