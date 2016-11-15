<?php
namespace rtens\udity;

/**
 * A CommandHandler first receives Events to restore its state and then is asked to turn a Command into new Events
 */
interface CommandHandler {

    /**
     * @param Command $command
     * @return Event[]
     */
    public function handle(Command $command);

    /**
     * @param Event $event
     * @return void
     */
    public function apply(Event $event);
}