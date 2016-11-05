<?php
namespace rtens\proto\app\ui;

use rtens\domin\delivery\web\WebApplication;
use rtens\proto\AggregateIdentifier;
use rtens\proto\app\Application;
use rtens\proto\app\ui\factories\AggregateActionFactory;
use rtens\proto\app\ui\factories\ProjectionActionFactory;
use rtens\proto\app\ui\factories\SingletonActionFactory;
use rtens\proto\app\ui\factories\DomainObjectActionFactory;
use rtens\proto\app\ui\fields\IdentifierEnumerationField;
use rtens\proto\app\ui\fields\IdentifierField;
use rtens\proto\utils\Str;

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
}