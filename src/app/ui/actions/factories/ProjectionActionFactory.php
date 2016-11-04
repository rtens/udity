<?php
namespace rtens\proto\app\ui\actions\factories;

use rtens\domin\Action;
use rtens\domin\delivery\web\WebApplication;
use rtens\proto\app\Application;
use rtens\proto\app\ui\ActionFactory;
use rtens\proto\app\ui\actions\QueryAction;
use rtens\proto\domain\objects\DomainObject;
use rtens\proto\Projection;

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
        return $class->isSubclassOf(Projection::class) && $class->getName() != DomainObject::class;
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