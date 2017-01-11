<?php
namespace rtens\udity\app\ui;

interface WebInterfacePlugin {

    /**
     * @param string[] $domainClasses
     * @return void
     */
    public function prepare(array $domainClasses);
}