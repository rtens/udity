<?php
namespace rtens\proto\app\ui;

use rtens\domin\Action;
use rtens\domin\delivery\web\WebApplication;
use rtens\proto\app\Application;
use rtens\proto\Projection;

class QueryActionFactory implements ActionFactory {
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
     * @return string
     */
    public function getClass() {
        return Projection::class;
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