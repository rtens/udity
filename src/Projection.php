<?php
namespace rtens\proto;

/**
 * A Projection receives all Events that were ever recorded to infer something interesting from them
 */
interface Projection {

    /**
     * @param Event $event
     * @return void
     */
    public function apply(Event $event);
}