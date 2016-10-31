<?php
namespace rtens\proto;

class DomainObject extends AggregateRoot {

    public function handle(Command $command) {
        if ($command->getName() == 'create') {
            return new Event($command->getAggregateIdentifier(), 'Created', $command->getArguments());
        }

        return [];
    }
}