<?php
namespace rtens\proto\app;

use rtens\proto\AggregateIdentifier;
use rtens\proto\domain\command\Aggregate;

class ClassGenerator {

    public function inferClasses(array $classes) {
        foreach ($classes as $class) {
            if (is_subclass_of($class, Aggregate::class)) {
                $this->defineClass($classes, $class . 'Identifier', AggregateIdentifier::class);
            }
        }

        return $classes;
    }

    private function defineClass(array& $classes, $fullName, $baseClass) {
        if (in_array($fullName, $classes)) {
            return $classes;
        }
        $classes[] = $fullName;

        $parts = explode('\\', $fullName);
        $shortName = array_pop($parts);
        $nameSpace = implode('\\', $parts);

        eval("namespace $nameSpace; class $shortName extends \\" . $baseClass . " {}");

        return $classes;
    }
}