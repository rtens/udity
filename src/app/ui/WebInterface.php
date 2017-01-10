<?php
namespace rtens\udity\app\ui;

use rtens\domin\delivery\web\renderers\link\LinkPrinter;
use rtens\domin\delivery\web\renderers\link\types\ClassLink;
use rtens\domin\delivery\web\WebApplication;
use rtens\domin\reflection\GenericAction;
use rtens\udity\AggregateIdentifier;
use rtens\udity\app\Application;
use rtens\udity\app\ui\factories\AggregateActionFactory;
use rtens\udity\app\ui\factories\DomainObjectActionFactory;
use rtens\udity\app\ui\factories\ProjectionActionFactory;
use rtens\udity\app\ui\factories\SingletonActionFactory;
use rtens\udity\app\ui\fields\IdentifierEnumerationField;
use rtens\udity\app\ui\fields\IdentifierField;
use rtens\udity\app\ui\renderers\CaptionedIdentifierRenderer;
use rtens\udity\app\ui\renderers\IdentifierRenderer;
use rtens\udity\app\ui\renderers\ProjectionListRenderer;
use rtens\udity\domain\objects\DomainObject;
use rtens\udity\utils\Str;
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
            new SingletonActionFactory($this->app, $this->ui),
            new DomainObjectActionFactory($this->app, $this->ui),
            new AggregateActionFactory($this->app, $this->ui),
            new ProjectionActionFactory($this->app, $this->ui),
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
        $this->registerRenderers($domainClasses);
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
            if (!(Str::g($class)->endsWith('Identifier') && is_subclass_of($class, AggregateIdentifier::class))) {
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
                $linkables[$class][$class]['$$'] = function (AggregateIdentifier $object) {
                    return $object->getKey();
                };
            }

            $reader = new PropertyReader($this->ui->types, $class);
            foreach ($reader->readInterface() as $property) {
                $type = $property->type();
                if (!$property->canGet() || !($type instanceof ClassType)) {
                    continue;
                }

                if (is_subclass_of($class, DomainObject::class) && $type->getClass() == AggregateIdentifier::class) {
                    $type = new ClassType($class . 'Identifier');
                }

                if (is_subclass_of($type->getClass(), AggregateIdentifier::class)) {
                    $identifierClass = $type->getClass();
                    $linkables[$identifierClass][$class][$property->name()] = function ($object) use ($property) {
                        /** @var AggregateIdentifier $identifier */
                        $identifier = $property->get($object);
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
                        foreach ($getters as $property => $getter) {
                            $linkId = $actionId;

                            $isDefaultProperty = in_array($property, ['$$', 'identifier']);
                            $isDefaultParameter = in_array($parameter->getName(), ['target', 'identifier']);

                            if (!$isDefaultProperty || (!$isDefaultParameter)) {
                                $linkAction = new GenericAction($this->ui->actions->getAction($actionId));
                                $details = '';
                                if (!$isDefaultProperty) {
                                    $details .= $property;
                                }
                                if (!$isDefaultParameter) {
                                    $details .= '->' . $parameter->getName();
                                }
                                $linkAction->setCaption($linkAction->caption() . '(' . $details . ')');
                                $linkId = $actionId . '$' . $property . '$' . $parameter->getName();
                                $this->ui->actions->add($linkId, $linkAction);
                            }

                            $this->ui->links->add(new ClassLink($class, $linkId,
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

    private function registerRenderers($classes) {
        $linkPrinter = new LinkPrinter($this->ui->links, $this->ui->actions, $this->ui->parser, $this->ui->token);

        $this->ui->renderers->add(new ProjectionListRenderer($this->ui->renderers, $this->ui->types, $linkPrinter));

        foreach ($classes as $class) {
            if (!(Str::g($class)->endsWith('Identifier') && is_subclass_of($class, AggregateIdentifier::class))) {
                continue;
            }

            $listClass = Str::g($class)->before('Identifier') . 'List';
            if (in_array($listClass, $classes)) {
                $this->ui->renderers->add(new CaptionedIdentifierRenderer($this->app, $listClass, $this->ui->renderers, $this->ui->types, $linkPrinter));
            } else {
                $this->ui->renderers->add(new IdentifierRenderer($this->ui->renderers, $this->ui->types, $linkPrinter));
            }
        }

    }
}