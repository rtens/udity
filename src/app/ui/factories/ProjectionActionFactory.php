<?php
namespace rtens\udity\app\ui\factories;

use rtens\domin\Action;
use rtens\domin\delivery\web\WebApplication;
use rtens\udity\app\Application;
use rtens\udity\app\ui\ActionFactory;
use rtens\udity\app\ui\actions\QueryAction;
use rtens\udity\domain\objects\DomainObject;
use rtens\udity\domain\objects\DomainObjectList;
use rtens\udity\domain\query\DefaultProjection;
use rtens\udity\domain\query\ProjectionList;
use rtens\udity\Projection;
use rtens\udity\utils\Str;

class ProjectionActionFactory implements ActionFactory {
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
     * @param \ReflectionClass $class
     * @return bool
     */
    public function handles(\ReflectionClass $class) {
        $aggregateClass = Str::g($class->getName())->before('List');

        return
            $class->isSubclassOf(Projection::class)
            && !in_array($class->getName(), [
                DomainObject::class,
                DefaultProjection::class,
                DomainObjectList::class,
                ProjectionList::class
            ])
            && (!class_exists($aggregateClass)
                || !is_subclass_of($aggregateClass, DomainObject::class));
    }

    /**
     * @param \ReflectionClass $class
     * @return Action[] indexed by their ID
     */
    public function buildActionsFrom(\ReflectionClass $class) {
        return [
            $class->getShortName() => new QueryAction($this->app, $class, $this->ui->types),
        ];
    }
}