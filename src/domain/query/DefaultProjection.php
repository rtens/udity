<?php
namespace rtens\proto\domain\query;

use rtens\proto\Event;
use rtens\proto\Projection;
use rtens\proto\utils\ArgumentFiller;

class DefaultProjection implements Projection {

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
            ->invoke($this, $event->getPayload());
    }
}