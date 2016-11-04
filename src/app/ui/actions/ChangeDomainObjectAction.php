<?php
namespace rtens\proto\app\ui\actions;

use rtens\proto\Query;
use rtens\proto\utils\Str;

class ChangeDomainObjectAction extends AggregateCommandAction {

    public function fill(array $parameters) {
        $parameters = parent::fill($parameters);

        $class = $this->method->getDeclaringClass();

        $getter = 'get' . Str::g($this->method->getName())->after('set');
        if (!$class->hasMethod($getter)) {
            return $parameters;
        }

        if (array_key_exists(AggregateCommandAction::IDENTIFIER_KEY, $parameters)) {
            $projection = $this->app->execute(new Query($class->getName(), [
                'identifier' => $parameters[AggregateCommandAction::IDENTIFIER_KEY]
            ]));

            $parameterName = $this->method->getParameters()[0]->getName();
            $parameters[$parameterName] = $class->getMethod($getter)->invoke($projection);
        }

        return $parameters;
    }
}