<?php

class Amino {

    function sortObject() {
        if (obj === null) return null
        if (typeof obj !== "object") return obj
        // arrays have typeof "object" in js!
        if (Array.isArray(obj)){
          return obj.map(sortObject);
        }
        $sortedKeys = Object.keys(obj).sort();
        $result = {};
        sortedKeys.forEach(key => {
          result[key] = sortObject(obj[key])
        })
        return result;
      }


      /**
     * @param obj -- {object}
     * @return bytes {Buffer}
     */
    function convertObjectToSignBytes(){
        Buffer.from(JSON.stringify(sortObject(obj)));
    }

}

?>
