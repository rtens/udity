<?php
namespace rtens\proto;

/**
 * Can apply Events.
 */
interface Projecting {

    /**
     * @param Event $event
     * @return void
     */
    public function apply(Event $event);
}