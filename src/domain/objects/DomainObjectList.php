<?php
namespace rtens\udity\domain\objects;

use rtens\udity\domain\query\IdentifierOptionsList;
use rtens\udity\domain\query\ProjectionList;
use rtens\udity\Event;
use rtens\udity\Projection;
use rtens\udity\utils\Str;

abstract class DomainObjectList extends ProjectionList implements IdentifierOptionsList {

    /**
     * @param Event $event
     * @return bool
     */
    protected function matchesEvent(Event $event) {
        return $this->inferDomainObjectClass()->getName() == $event->getAggregateIdentifier()->getName();
    }

    /**
     * @param Event $event
     * @return Projection
     */
    protected function createItem(Event $event) {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->inferDomainObjectClass()->newInstance($event->getAggregateIdentifier());
    }

    /**
     * @return \ReflectionClass
     */
    protected function inferDomainObjectClass() {
        return new \ReflectionClass(Str::g(get_class($this))->before('List'));
    }

    /**
     * @return string[] captions indexed by keys
     */
    public function options() {
        return array_map(function (DomainObject $object) {
            return $object->caption();
        }, $this->getItems());
    }
}