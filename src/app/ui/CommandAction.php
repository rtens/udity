<?php
namespace rtens\proto\app\ui;

use rtens\domin\Action;
use rtens\domin\Parameter;

/**
 * Builds a Command from parameters inferred from a method
 */
class CommandAction implements Action {
    const IDENTIFIER_KEY = 'target';

    /**
     * @return string
     */
    public function caption() {
        return '';
    }

    /**
     * @return string|null
     */
    public function description() {
        return '';
    }

    /**
     * @return boolean True if the action modifies the state of the application
     */
    public function isModifying() {
        return true;
    }

    /**
     * @return Parameter[]
     */
    public function parameters() {
        return [];
    }

    /**
     * Fills out partially available parameters
     *
     * @param array $parameters Available values indexed by name
     * @return array Filled values indexed by name
     */
    public function fill(array $parameters) {
        return $parameters;
    }

    /**
     * @param mixed[] $parameters Values indexed by name
     * @return void
     * @throws \Exception if Action cannot be executed
     */
    public function execute(array $parameters) {
    }
}