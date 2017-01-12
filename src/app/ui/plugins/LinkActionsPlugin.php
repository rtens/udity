<?php
namespace rtens\udity\app\ui\plugins;

use rtens\domin\Action;
use rtens\domin\delivery\web\renderers\link\Link;
use rtens\domin\delivery\web\renderers\link\types\GenericLink;
use rtens\domin\delivery\web\WebApplication;
use rtens\domin\Parameter;
use rtens\domin\reflection\GenericAction;
use rtens\udity\AggregateIdentifier;
use rtens\udity\app\Application;
use rtens\udity\app\ui\WebInterfacePlugin;
use rtens\udity\domain\objects\DomainObject;
use watoki\reflect\PropertyReader;
use watoki\reflect\Type;
use watoki\reflect\type\ClassType;
use watoki\reflect\type\NullableType;

class LinkActionsPlugin implements WebInterfacePlugin {
    /**
     * @var WebApplication
     */
    private $ui;
    /**
     * @var Application
     */
    private $app;

    /**
     * @param WebApplication $ui
     * @param Application $app
     */
    public function __construct(WebApplication $ui, Application $app) {
        $this->ui = $ui;
        $this->app = $app;
    }

    /**
     * @param string[] $domainClasses
     * @return void
     */
    public function prepare(array $domainClasses) {
        $this->linkActions($this->collectLinkables($domainClasses));
    }

    /**
     * @param array $domainClasses
     * @return callable[][][]
     */
    private function collectLinkables(array $domainClasses) {
        $linkables = [];

        foreach ($domainClasses as $class) {
            if (is_subclass_of($class, AggregateIdentifier::class)) {
                $linkables[$class][$class]['$$'] = function (AggregateIdentifier $object) {
                    return $object->getKey();
                };
            }

            $reader = new PropertyReader($this->ui->types, $class);
            foreach ($reader->readInterface() as $property) {
                $type = $property->type();
                if (!$property->canGet()) {
                    continue;
                }

                $isIdentifier = $type instanceof ClassType && $type->getClass() == AggregateIdentifier::class;
                if (is_subclass_of($class, DomainObject::class) && $isIdentifier) {
                    $type = new ClassType($class . 'Identifier');
                }

                $identifierClass = $this->determineIdentifierClass($type);
                if ($identifierClass) {
                    $linkables[$identifierClass][$class][$property->name()] = function ($object) use ($property) {
                        /** @var AggregateIdentifier $identifier */
                        $identifier = $property->get($object);
                        if (!$identifier) {
                            return null;
                        }
                        return $identifier->getKey();
                    };
                }
            }
        }

        return $linkables;
    }

    /**
     * @param callable[][][] $linkables
     */
    private function linkActions($linkables) {
        foreach ($this->ui->actions->getAllActions() as $actionId => $action) {
            $this->linkAction($linkables, $actionId, $action);
        }
    }

    /**
     * @param callable[][][] $linkables
     * @param $actionId
     * @param Action $action
     */
    private function linkAction($linkables, $actionId, Action $action) {
        foreach ($action->parameters() as $parameter) {
            $identifierClass = $this->determineIdentifierClass($parameter->getType());
            if ($identifierClass) {
                if (!array_key_exists($identifierClass, $linkables)) {
                    continue;
                }

                $this->linkActionsToIdentifier($linkables, $actionId, $parameter, $identifierClass);
            }
        }
    }

    private function determineIdentifierClass(Type $type) {
        if ($type instanceof NullableType) {
            $type = $type->getType();
        }
        if ($type instanceof ClassType && is_subclass_of($type->getClass(), AggregateIdentifier::class)) {
            return $type->getClass();
        }
        return null;
    }

    /**
     * @param callable[][][] $linkables
     * @param string $actionId
     * @param Parameter $parameter
     * @param $identifierClass
     */
    private function linkActionsToIdentifier($linkables, $actionId, $parameter, $identifierClass) {
        foreach ($linkables[$identifierClass] as $class => $getters) {
            foreach ($getters as $property => $getter) {
                $this->linkProperty($actionId, $parameter, $property, $class, $getter);
            }
        }
    }

    /**
     * @param string $actionId
     * @param Parameter $parameter
     * @param string $property
     * @param string $class
     * @param callable $getter
     */
    private function linkProperty($actionId, Parameter $parameter, $property, $class, $getter) {
        $linkId = $actionId;

        if (!($this->isDefaultProperty($property)) || (!($this->isDefaultParameter($parameter)))) {
            $linkId = $this->registerLinkAction($actionId, $parameter, $property);
        }

        $this->ui->links->add($this->createLink($parameter, $class, $getter, $linkId));
    }

    /**
     * @param string $actionId
     * @param Parameter $parameter
     * @param string $property
     * @return string
     */
    private function registerLinkAction($actionId, Parameter $parameter, $property) {
        $linkId = $actionId . '$' . $property . '$' . $parameter->getName();

        $linkAction = new GenericAction($this->ui->actions->getAction($actionId));
        $linkAction->setCaption($this->createLinkActionCaption($parameter, $property, $linkAction));

        $this->ui->actions->add($linkId, $linkAction);

        return $linkId;
    }

    /**
     * @param Parameter $parameter
     * @param string $property
     * @param Action $linkAction
     * @return string
     */
    private function createLinkActionCaption(Parameter $parameter, $property, $linkAction) {
        $details = '';
        if (!$this->isDefaultProperty($property)) {
            $details .= $property;
        }
        if (!$this->isDefaultParameter($parameter)) {
            $details .= '->' . $parameter->getName();
        }
        return $linkAction->caption() . '(' . $details . ')';
    }

    /**
     * @param Parameter $parameter
     * @param string $class
     * @param callable $getter
     * @param string $linkId
     * @return Link
     */
    private function createLink(Parameter $parameter, $class, $getter, $linkId) {
        return new GenericLink(
            $linkId,
            function ($object) use ($class, $getter) {
                return is_a($object, $class) && $getter($object) != null;
            },
            function ($object) use ($parameter, $getter) {
                return [
                    $parameter->getName() => [
                        'key' => $getter($object),
                        'fix' => true
                    ],
                ];
            });
    }

    /**
     * @param $property
     * @return bool
     */
    private function isDefaultProperty($property) {
        return in_array($property, ['$$', 'identifier']);
    }

    /**
     * @param Parameter $parameter
     * @return bool
     */
    private function isDefaultParameter(Parameter $parameter) {
        return in_array($parameter->getName(), ['target', 'identifier']);
    }
}