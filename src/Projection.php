<?php
namespace rtens\proto;

/**
 * Applies Events using named methods.
 *
 * e.g. the event 'Foo' is forwarded to applyFoo()
 */
class Projection implements Projecting {

    /**
     * @param Event $event
     * @return void
     */
    public function apply(Event $event) {
        $method = 'apply' . $event->getName();
        if (!method_exists($this, $method)) {
            return;
        }

        ArgumentFiller::from($this, $method)
            ->inject(Event::class, $event)
            ->invoke($this, $event->getArguments());
    }
}