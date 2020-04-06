<?php

namespace Binance\Encoder;

class Encoder {

    function sortObject($obj) {
        $myArray = json_decode(json_encode($obj), true); 
        ksort($myArray);
        foreach($myArray as &$value) {
          if(is_array($value)){
            ksort($value);

            foreach($value as &$subvalue) {
              if(is_array($subvalue)){
                ksort($subvalue);
              }

              foreach($subvalue as &$subsubvalue) {
                if(is_array($subsubvalue)){
                  ksort($subsubvalue);
                  foreach($subsubvalue as &$subsubsubvalue) {
                    if(is_array($subsubsubvalue)){
                      ksort($subsubsubvalue);
                    }
                  }
                }
              }
            }

          }
        }
        return $myArray;
      }


      /**
     * @param obj -- {object}
     * @return bytes {Buffer}
     */
    function convertObjectToSignBytes($obj){
      $sorted = $this->sortObject($obj);
      $php_string = json_encode($sorted);
      return($php_string);
    }

}

?>
