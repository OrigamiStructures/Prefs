<?php


namespace Prefs\Exception;


use Cake\Core\Exception\Exception;

class BadPrefsImplementationException extends Exception
{
    public function __construct($message, $code = 500, $previous = null)
    {
        /**
         * @todo We should catch these and let the user continue
         */

        parent::__construct($message, $code, $previous);
    }
}
