<?php
namespace spec\rtens\proto;

use rtens\domin\delivery\web\WebApplication;
use rtens\proto\Application;
use rtens\proto\Event;
use rtens\proto\GenericAggregateIdentifier;
use rtens\proto\Projection;
use rtens\proto\Time;
use rtens\scrut\Assert;
use watoki\factory\Factory;
use watoki\karma\stores\EventStore;
use watoki\karma\stores\MemoryEventStore;

class FindAndExecuteProjectionsSpec {

    /**
     * @var EventStore
     */
    private $events;
    /**
     * @var WebApplication
     */
    private $domin;

    public function before() {
        Time::freeze();

        $this->events = new MemoryEventStore();
        $this->domin = (new Factory())->getInstance(WebApplication::class);
    }

    private function runApp() {
        $app = new Application($this->events);
        $app->run($this->domin);
    }

    private function project($projection, $arguments = []) {
        $this->runApp();
        return $this->domin->actions->getAction($projection)->execute($arguments);
    }

    private function id($aggregate, $key = null) {
        return new GenericAggregateIdentifier('proto\test\domain\\' . $aggregate, $key ?: $aggregate);
    }

    #########################################################################################

    function projectionDoesNotExist(Assert $assert) {
        try {
            $this->project('Foo');
            $assert->fail();
        } catch (\Exception $exception) {
            $assert->pass();
        }
    }

    function emptyProjection(Assert $assert) {
        eval('namespace proto\test\domain;
        class EmptyProjection extends \\' . Projection::class . ' {}');

        $result = $this->project('EmptyProjection');
        $assert(is_object($result));
        $assert(get_class($result), 'proto\test\domain\EmptyProjection');
    }

    function applyEvents(Assert $assert) {
        eval('namespace proto\test\domain;
        class ProjectEvents extends \\' . Projection::class . ' {
            function applyThat($two, \rtens\proto\Event $e, $one) {
                $this->applied = $e->getName() . $one . $two;
            }
        }');

        $this->events->append(new Event($this->id('foo'), 'NotThis'), $this->id('foo'));
        $this->events->append(new Event($this->id('foo'), 'That', ['one' => 'And', 'two' => 'This']), $this->id('foo'));

        $result = $this->project('ProjectEvents');
        $assert($result->applied, 'ThatAndThis');
    }
}