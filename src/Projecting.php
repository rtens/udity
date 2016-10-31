<?php
namespace rtens\proto;

interface Projecting {

    /**
     * @param Event $event
     * @return void
     */
    public function apply(Event $event);
}