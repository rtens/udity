<?php
namespace rtens\proto\app\ui;

use rtens\domin\delivery\web\renderers\link\types\ClassLink;
use rtens\domin\delivery\web\WebApplication;
use rtens\proto\AggregateIdentifier;
use rtens\proto\app\Application;
use rtens\proto\app\ui\factories\AggregateActionFactory;
use rtens\proto\app\ui\factories\DomainObjectActionFactory;
use rtens\proto\app\ui\factories\ProjectionActionFactory;
use rtens\proto\app\ui\factories\SingletonActionFactory;
use rtens\proto\app\ui\fields\IdentifierEnumerationField;
use rtens\proto\app\ui\fields\IdentifierField;
use rtens\proto\domain\objects\DomainObject;
use rtens\proto\utils\Str;
use watoki\reflect\PropertyReader;
use watoki\reflect\type\ClassType;

/**
 * Prepares the web interface (e.g. registers Actions, Links and Field)
 */
class WebInterface {
    /**
     * @var Application
     */
    private $app;
    /**
     * @var WebApplication
     */
    private $ui;

    /**
     * @param Application $app
     * @param WebApplication $ui
     */
    public function __construct(Application $app, WebApplication $ui) {
        $this->app = $app;
        $this->ui = $ui;
    }

    /**
     * @return ActionFactory[]
     */
    protected function buildActionFactories() {
        return [
            new ProjectionActionFactory($this->app, $this->ui),
            new SingletonActionFactory($this->app, $this->ui),
            new DomainObjectActionFactory($this->app, $this->ui),
            new AggregateActionFactory($this->app, $this->ui),
        ];
    }

    /**
     * @param string[] $domainClasses
     */
    public function prepare(array $domainClasses) {
        $this->ui->types = new DefaultTypeFactory();

        $this->registerIdentifierFields($domainClasses);
        $this->registerActions($domainClasses);
        $this->linkActions($domainClasses);
    }

    private function registerActions(array $domainClasses) {
        foreach ($domainClasses as $class) {
            $this->registerActionsOf(new \ReflectionClass($class));
        }
    }

    private function registerActionsOf($class) {
        foreach ($this->buildActionFactories() as $factory) {
            if ($factory->handles($class)) {
                $this->registerActionBuiltBy($class, $factory);
            }
        }
    }

    private function registerActionBuiltBy(\ReflectionClass $class, ActionFactory $factory) {
        foreach ($factory->buildActionsFrom($class) as $id => $action) {
            $this->ui->actions->add($id, $action);
            $this->ui->groups->put($id, $class->getShortName());
        }
    }

    private function registerIdentifierFields(array $classes) {
        foreach ($classes as $class) {
            $endsWith = Str::g($class)->endsWith('Identifier');
            $is_subclass_of = is_subclass_of($class, AggregateIdentifier::class);
            if (!$endsWith || !$is_subclass_of) {
                continue;
            }

            $listClass = Str::g($class)->before('Identifier') . 'List';
            if (in_array($listClass, $classes)) {
                $this->ui->fields->add(new IdentifierEnumerationField($this->app, $listClass, $class));
            } else {
                $this->ui->fields->add(new IdentifierField($class));
            }
        }
    }

    private function linkActions($classes) {
        /** @var callable[][][] $linkables */
        $linkables = [];

        foreach ($classes as $class) {
            if (is_subclass_of($class, AggregateIdentifier::class)) {
                $linkables[$class][$class][] = function (AggregateIdentifier $object) {
                    return $object->getKey();
                };
            }

            $reader = new PropertyReader($this->ui->types, $class);
            foreach ($reader->readInterface() as $getter) {
                if (!$getter->canGet()) {
                    continue;
                }
                $type = $getter->type();
                if (!($type instanceof ClassType)) {
                    continue;
                }
                if (is_subclass_of($class, DomainObject::class) && $type->getClass() == AggregateIdentifier::class) {
                    $type = new ClassType($class . 'Identifier');
                }

                if (is_subclass_of($type->getClass(), AggregateIdentifier::class)) {
                    $identifierClass = $type->getClass();
                    $linkables[$identifierClass][$class][] = function ($object) use ($getter) {
                        /** @var AggregateIdentifier $identifier */
                        $identifier = $getter->get($object);
                        return $identifier->getKey();
                    };
                }
            }
        }

        foreach ($this->ui->actions->getAllActions() as $actionId => $action) {
            foreach ($action->parameters() as $parameter) {
                $type = $parameter->getType();
                if ($type instanceof ClassType && is_subclass_of($type->getClass(), AggregateIdentifier::class)) {
                    $identifierClass = $type->getClass();
                    if (!array_key_exists($identifierClass, $linkables)) {
                        continue;
                    }

                    foreach ($linkables[$identifierClass] as $class => $getters) {
                        foreach ($getters as $getter) {
                            $this->ui->links->add(new ClassLink($class, $actionId,
                                function ($object) use ($parameter, $getter) {
                                    return [
                                        $parameter->getName() => [
                                            'key' => $getter($object),
                                            'fix' => true
                                        ],
                                    ];
                                }));
                        }
                    }
                }
            }
        }
    }
}