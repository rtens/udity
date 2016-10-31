<?php
namespace rtens\proto;

class Time {
    private static $frozen;

    public static function freeze($when = 'now') {
        self::$frozen = new \DateTimeImmutable($when);
    }

    public static function now() {
        return self::$frozen ?: new \DateTimeImmutable();
    }

    public static function at($timeString) {
        return new \DateTimeImmutable('@' . strtotime($timeString, self::now()->getTimestamp()));
    }
}