<?php

namespace Binance\Utils;

class UtilFunctions {
    static function strSlice($str, $start, $end) {
        $end = $end - $start;
        return substr($str, $start, $end);
    }
}

?>