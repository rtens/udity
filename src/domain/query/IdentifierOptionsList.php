<?php
namespace rtens\udity\domain\query;

use rtens\udity\Projection;

interface IdentifierOptionsList extends Projection {

    /**
     * @return string[] captions indexed by keys
     */
    public function options();
}