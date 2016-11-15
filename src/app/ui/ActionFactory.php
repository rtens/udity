<?php
namespace rtens\udity\app\ui;
use rtens\domin\Action;

/**
 * Infers Queries and Commands from a type and builds Actions for them
 */
interface ActionFactory {

    /**
     * @param \ReflectionClass $class
     * @return bool
     */
    public function handles(\ReflectionClass $class);

    /**
     * @param \ReflectionClass $class
     * @return Action[] indexed by their ID
     */
    public function buildActionsFrom(\ReflectionClass $class);
}