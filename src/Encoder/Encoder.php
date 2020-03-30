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
      //$php_string = str_replace('"', "", $php_string);
      //echo "<br/>hello2<br/>";
      //var_dump($php_string);
      return($php_string);
        //Buffer.from(JSON.stringify(sortObject(obj)));
    }

}

?>
