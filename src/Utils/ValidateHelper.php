<?php

namespace Binance\Utils;
use Exception;

define("MAX_INT64", pow(2,63));

class ValidateHelper {
    function checkNumber ($value,  $name="input number"){
        try {
            if ($value <= 0) {
                throw new Exception($name ." should be a positive number");
            }
            
            if (MAX_INT64 <= $value) {
                throw new Exception($name ." should be less than 2^63");
            }
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
}

?>