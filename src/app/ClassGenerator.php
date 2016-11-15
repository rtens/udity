<?php
namespace rtens\udity\app;

use rtens\udity\AggregateIdentifier;
use rtens\udity\domain\command\Aggregate;
use rtens\udity\domain\objects\DomainObject;
use rtens\udity\domain\objects\DomainObjectList;

class ClassGenerator {

    public function inferClasses(array $classes) {
        foreach ($classes as $class) {
            if ($this->hasBaseClass($class, Aggregate::class)) {
                $this->defineClass($classes, $class . 'Identifier', AggregateIdentifier::class);
            }
            if ($this->hasBaseClass($class, DomainObject::class)) {
                $this->defineClass($classes, $class . 'List', DomainObjectList::class);
            }
        }

        return $classes;
    }

    private function defineClass(array& $classes, $fullName, $baseClass) {
        if (in_array($fullName, $classes)) {
            return $classes;
        }
        $classes[] = $fullName;

        if (class_exists($fullName)) {
            return $classes;
        }

        $parts = explode('\\', $fullName);
        $shortName = array_pop($parts);
        $nameSpace = implode('\\', $parts);

        eval("namespace $nameSpace; class $shortName extends \\" . $baseClass . " {}");

        return $classes;
    }

    private function hasBaseClass($class, $baseClass) {
        $class = new \ReflectionClass($class);
        return !$class->isAbstract() && $class->isSubclassOf($baseClass);
    }
}