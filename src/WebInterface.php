<?php
namespace rtens\proto;

use rtens\domin\Action;
use rtens\domin\delivery\web\renderers\link\types\ClassLink;
use rtens\domin\delivery\web\WebApplication;
use watoki\reflect\Property;
use watoki\reflect\PropertyReader;
use watoki\reflect\type\ClassType;

class WebInterface {
    const PROJECTION_GROUP = 'Show';
    /**
     * @var Application
     */
    private $app;
    /**
     * @var WebApplication
     */
    private $ui;
    /**
     * @var string[]
     */
    private $knownClasses;

    public function __construct(Application $app, WebApplication $ui, array $knownClasses) {
        $this->ui = $ui;
        $this->app = $app;
        $this->knownClasses = $knownClasses;
    }

    public function prepare() {
        $this->ui->types = new DefaultTypeFactory();

        $this->registerProjections();
        $this->registerAggregates();
        $this->registerDomainObjects();

        $this->linkActions();
    }

    private function registerProjections() {
        foreach ($this->findSubClasses(Projecting::class) as $projection) {
            if ($projection->getName() == DomainObject::class) {
                continue;
            }

            $this->addQueryAction(self::PROJECTION_GROUP, $projection);
        }
    }

    private function registerAggregates() {
        foreach ($this->findSubClasses(AggregateRoot::class) as $root) {
            $this->defineClassIfNotExists($root->getName() . 'Identifier', AggregateIdentifier::class);

            foreach ($this->findCommandMethods($root) as $command => $method) {
                $this->addCommandAction($root->getShortName(), $method, $command);

                if ($root->isSubclassOf(SingletonAggregateRoot::class)) {
                    $this->ui->links->add(new ClassLink($root->getName(), $this->makeActionId($root, $command)));
                }
            }
        }
    }

    private function registerDomainObjects() {
        foreach ($this->findSubClasses(DomainObject::class) as $object) {
            $group = $object->getShortName();

            if ($object->hasMethod('created')) {
                $this->addCommandAction($group, $object->getMethod('created'), 'create');
            }

            $this->addQueryAction(self::PROJECTION_GROUP, $object);

            $this->defineClassIfNotExists($object->getName() . 'List', AggregateList::class);
            $this->addQueryAction(self::PROJECTION_GROUP, new \ReflectionClass($object->getName() . 'List'), 'all');

            foreach ($object->getMethods() as $method) {
                $methodName = Str::g($method->getName());

                if ($methodName->startsWithButIsNot('set') && $method->getNumberOfParameters() == 1) {
                    $this->addCommandAction($group, $method, 'change' . $methodName->after('set'))
                        ->setPostFill($this->fillPropertyFunction($method));

                } else if ($methodName->startsWithButIsNot('do')) {
                    $this->addCommandAction($group, $method, $methodName);

                } else if ($methodName->startsWithButIsNot('did')) {
                    $command = 'do' . $methodName->after('did');
                    if (!array_key_exists($object->getShortName() . '$' . $command, $this->ui->actions->getAllActions())) {
                        $this->addCommandAction($group, $method, $command);
                    }
                }
            }
        }
    }

    private function fillPropertyFunction(\ReflectionMethod $method) {
        return function ($parameters) use ($method) {
            $class = $method->getDeclaringClass();

            $getter = 'get' . Str::g($method->getName())->after('set');
            if (!$class->hasMethod($getter)) {
                return $parameters;
            }

            if (array_key_exists(CommandAction::IDENTIFIER_KEY, $parameters)) {
                $projection = $this->app->handle(new Query($class->getName(), [
                    'identifier' => $parameters[CommandAction::IDENTIFIER_KEY]
                ]));

                $parameterName = $method->getParameters()[0]->getName();
                $parameters[$parameterName] = $class->getMethod($getter)->invoke($projection);
            }
            return $parameters;
        };
    }

    /**
     * @param $baseClass
     * @return \ReflectionClass[]
     */
    private function findSubClasses($baseClass) {
        $classes = [];
        foreach ($this->knownClasses as $class) {
            if (is_subclass_of($class, $baseClass)) {
                $classes[] = new \ReflectionClass($class);
            }
        }
        return $classes;
    }

    /**
     * @param \ReflectionClass $rootClass
     * @return \ReflectionMethod[]
     */
    private function findCommandMethods(\ReflectionClass $rootClass) {
        $commandMethods = [];
        foreach ($rootClass->getMethods() as $method) {
            $methodName = Str::g($method->getName());
            if ($methodName->startsWithButIsNot('handle')) {
                $commandMethods[$methodName->after('handle')] = $method;
            }
        }
        return $commandMethods;
    }

    private function defineClassIfNotExists($fullName, $baseClass) {
        if (class_exists($fullName)) {
            return;
        }

        $parts = explode('\\', $fullName);
        $shortName = array_pop($parts);
        $nameSpace = implode('\\', $parts);

        eval("namespace $nameSpace; class $shortName extends \\" . $baseClass . " {}");
        $this->knownClasses[] = $fullName;
    }

    private function addCommandAction($group, \ReflectionMethod $method, $command = null) {
        $class = $method->getDeclaringClass();
        $action = new CommandAction($this->app, $command, $method, $this->ui->types, $this->ui->parser);
        $this->addAction($this->makeActionId($class, $command), $group, $action);

        return $action;
    }

    private function addQueryAction($group, \ReflectionClass $class, $command = null) {
        $this->addAction($this->makeActionId($class, $command), $group,
            new QueryAction($this->app, $class->getName(), $this->ui->types, $this->ui->parser));
    }

    private function addAction($id, $group, Action $action) {
        $this->ui->actions->add($id, $action);
        $this->ui->groups->put($id, $group);
    }

    private function makeActionId(\ReflectionClass $class, $command) {
        return $class->getShortName() . ($command ? '$' . $command : '');
    }

    private function linkActions() {
        $actions = $this->findIdentifiedActions();

        $this->linkEntities($actions, $this->findSubClasses(DomainObject::class));
        $this->linkEntities($actions, $this->findSubClasses(Projecting::class));
        $this->linkEntities($actions, $this->findSubClasses(AggregateIdentifier::class));
    }

    private function findIdentifiedActions() {
        $identifiedActions = [];
        foreach ($this->ui->actions->getAllActions() as $id => $action) {
            foreach ($action->parameters() as $parameter) {
                $identifierClass = $this->getIdentifierClass($parameter->getType());
                if ($identifierClass) {
                    $identifiedActions[$identifierClass][$parameter->getName()][] = $id;
                }
            }
        }
        return $identifiedActions;
    }

    /**
     * @param $identifiedActions
     * @param \ReflectionClass[] $entities
     */
    private function linkEntities($identifiedActions, $entities) {
        foreach ($entities as $entity) {
            foreach ($this->readGettableProperties($entity) as $property) {
                $type = $property->type();
                if ($entity->isSubclassOf(DomainObject::class) && $property->name() == 'identifier') {
                    $type = new ClassType($entity->getName() . 'Identifier');
                } else if ($entity->isSubclassOf(AggregateIdentifier::class) && $property->name() == 'key') {
                    $type = new ClassType($entity->getName());
                }

                $identifierType = $this->getIdentifierClass($type);
                if (!$identifierType) {
                    continue;
                }

                if (!array_key_exists($identifierType, $identifiedActions)) {
                    continue;
                }

                foreach ($identifiedActions[$identifierType] as $parameter => $actions) {
                    foreach ($actions as $action) {
                        $this->linkEntity($entity, $parameter, $action, $property);
                    }
                }
            }
        }
    }

    private function getIdentifierClass($type) {
        if ($type instanceof ClassType && is_subclass_of($type->getClass(), AggregateIdentifier::class)) {
            return $type->getClass();
        }
        return null;
    }

    /**
     * @param \ReflectionClass $entity
     * @return Property[]
     */
    private function readGettableProperties($entity) {
        $reader = new PropertyReader($this->ui->types, $entity->getName());
        return $reader->readInterface()->filter(function (Property $property) {
            return $property->canGet();
        })->toArray();
    }

    private function linkEntity(\ReflectionClass $entity, $parameter, $action, Property $property) {
        $this->ui->links->add(new ClassLink($entity->getName(), $action,
            function ($object) use ($parameter, $property) {
                $value = $property->get($object);

                if ($value instanceof AggregateIdentifier) {
                    $value = $value->getKey();
                }

                return [
                    $parameter => ['key' => $value]
                ];
            }));
    }
}