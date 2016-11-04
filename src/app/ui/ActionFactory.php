<?php
namespace rtens\proto\app\ui;
use rtens\domin\Action;

/**
 * Infers Queries and Commands from a type and builds Actions for them
 */
interface ActionFactory {

    /**
     * @return string
     */
    public function getClass();

    /**
     * @param \ReflectionClass $class
     * @return Action[] indexed by their ID
     */
    public function buildActionsFrom(\ReflectionClass $class);
}