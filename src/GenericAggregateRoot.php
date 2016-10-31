<?php
namespace rtens\proto;

class GenericAggregateRoot extends AggregateRoot {

    /**
     * @var callable[]
     */
    private $handlers = [];

    public function handle(Command $command) {
        if (array_key_exists($command->getName(), $this->handlers)) {
            call_user_func($this->handlers[$command->getName()], $command->getArguments());
        }
    }
}