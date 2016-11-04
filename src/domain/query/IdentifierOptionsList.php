<?php
namespace rtens\proto\domain\query;

use rtens\proto\Projection;

interface IdentifierOptionsList extends Projection {

    /**
     * @return string[] captions indexed by keys
     */
    public function getOptions();
}