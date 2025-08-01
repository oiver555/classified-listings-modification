<?php

namespace Halwaytovegas\ClassifiedListingModifications\Traits;

trait Singleton {
    public static $instance = null;

    public static function get_instance() {
        if( self::$instance == null ) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}