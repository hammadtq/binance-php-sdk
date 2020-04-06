<?php

namespace Binance\Token;

use Binance\Crypto\Address;
use Binance\Exception;

class Token {

    private $_bncClient;

    function __construct($bncClient) {
        $this->_bncClient = $bncClient;
    }

    /**
   * lock token for a while
   * @param {String} fromAddress
   * @param {String} description
   * @param {Array} amount
   * @param {Number} lockTime
   * @returns {Promise}  resolves with response (success or fail)
   */
  function timeLock($fromAddress, $description, $amount, $lockTime) {

    if (strlen($description) > 128) {
      throw new Exception("description is too long");
    }

    if ($lockTime < 60 || $lockTime > 253402300800) {
      throw new Exception("timeTime must be in [60, 253402300800]");
    }

    $address = new Address();
    $fromCode = $address->DecodeAddress($fromAddress);

    $timeLockMsg = (object)(array('from' => $fromCode, 
        'description' => $description,
        'amount' => $amount,
        'lock_time' => $lockTime,
        'msgType' => 'TimeLockMsg',
    ));

    $signTimeLockMsg = (object)(array('from' => $fromAddress, 
        'description' => $description,
        'amount' => [$amount],
        'lock_time' => $lockTime,
    ));

    return ($this->_bncClient->_prepareTransaction($timeLockMsg, $signTimeLockMsg, $fromAddress));
  }


  /**
   * lock more token or increase locked period
   * @param {String} fromAddress
   * @param {Number} id
   * @param {String} description
   * @param {Array} amount
   * @param {Number} lockTime
   * @returns {Promise}  resolves with response (success or fail)
   */
  function timeRelock($fromAddress, $id, $description, $amount, $lockTime) {

    if (strlen($description) > 128) {
      throw new Exception("description is too long");
    }

    if ($lockTime < 60 || $lockTime > 253402300800) {
      throw new Exception("timeTime must be in [60, 253402300800]");
    }

    $address = new Address();
    $fromCode = $address->DecodeAddress($fromAddress);

    $timeRelockMsg = (object)(array('from' => $fromCode, 
        'time_lock_id' => $id,
        'description' => $description,
        'amount' => $amount,
        'lock_time' => $lockTime,
        'msgType' => 'TimeRelockMsg',
    ));

    $signTimeRelockMsg = (object)(array('from' => $fromAddress, 
        'time_lock_id' => $id,
        'description' => $description,
        'amount' => [$amount],
        'lock_time' => $lockTime
    ));

    return ($this->_bncClient->_prepareTransaction($timeRelockMsg, $signTimeRelockMsg, $fromAddress));
  }

  /**
   * unlock locked tokens
   * @param {String} fromAddress
   * @param {Number} id
   * @returns {Promise}  resolves with response (success or fail)
   */
  function timeUnlock($fromAddress, $id) {

    $address = new Address();
    $fromCode = $address->DecodeAddress($fromAddress);

    $timeUnlockMsg = (object)(array('from' => $fromCode, 
        'time_lock_id' => $id,
        'msgType' => 'TimeUnlockMsg',
    ));

    $signTimeUnlockMsg = (object)(array('from' => $fromAddress, 
        'time_lock_id' => $id
    ));

    return ($this->_bncClient->_prepareTransaction($timeUnlockMsg, $signTimeUnlockMsg, $fromAddress));
  }

}

?>