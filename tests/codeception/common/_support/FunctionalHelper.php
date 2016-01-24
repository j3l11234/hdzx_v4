<?php

namespace tests\codeception\common\_support;

use Codeception\Module;

/**
 * This helper is used to populate the database with needed fixtures before any tests are run.
 * In this example, the database is populated with the demo login user, which is used in acceptance
 * and functional tests.  All fixtures will be loaded before the suite is started and unloaded after it
 * completes.
 */
class FunctionalHelper extends Module{

    public function seeExceptionThrown($exception, $function) {       
        try {
            $function();
            return false;   
        } catch (Exception $e)  {
            if( get_class($e) == $exception ) {
                return true;            
            }
                return false;
        }
    }
}
