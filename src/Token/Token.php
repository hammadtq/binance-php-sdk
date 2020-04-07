<?php

namespace Binance\Token;

use Binance\Crypto\Address;
use Brick\Math\BigDecimal;
use Binance\Exception;

define("MAXTOTALSUPPLY", 9000000000000000000);

class Token {

    private $_bncClient;

    function __construct($bncClient) {
        $this->_bncClient = $bncClient;
    }

    /**
   * Freeze a token.
   * @param {String} symbol the market pair
   * @param {String} refid the order ID of the order to cancel
   * @param {Number} sequence optional sequence
   * @return {Promise} resolves with response (success or fail)
   */
  function tokenFreeze($accAddress, $symbol, $amount, $sequence = null) {
    $address = new Address();
    $accCode = $address->DecodeAddress($accAddress);

    $amount = strval(BigDecimal::of($amount)->multipliedBy(BASENUMBER));

    $TokenFreeze = (object)(array('from' => $accCode, 
        'symbol' => $symbol,
        'amount' => (int)$amount,
        'msgType' => "FreezeMsg"
    ));

    $signMsg = (object)(array('amount' => (int)$amount, 
        'from' => $accAddress,
        'symbol' => $symbol
    ));

    return ($this->_bncClient->_prepareTransaction($TokenFreeze, $signMsg, $accAddress, $sequence, ""));
  }

  /**
   * UnFreeze a token.
   * @param {String} symbol the market pair
   * @param {String} refid the order ID of the order to cancel
   * @param {Number} sequence optional sequence
   * @return {Promise} resolves with response (success or fail)
   */
  function tokenUnFreeze($accAddress, $symbol, $amount, $sequence = null) {
    $address = new Address();
    $accCode = $address->DecodeAddress($accAddress);

    $amount = strval(BigDecimal::of($amount)->multipliedBy(BASENUMBER));

    $TokenUnFreeze = (object)(array('from' => $accCode, 
        'symbol' => $symbol,
        'amount' => (int)$amount,
        'msgType' => "UnFreezeMsg"
    ));

    $signMsg = (object)(array('amount' => (int)$amount, 
        'from' => $accAddress,
        'symbol' => $symbol
    ));

    return ($this->_bncClient->_prepareTransaction($TokenUnFreeze, $signMsg, $accAddress, $sequence, ""));
  }

  /**
   * create a new asset on Binance Chain
   * @param {String} - senderAddress
   * @param {String} - tokenName
   * @param {String} - symbol
   * @param {Number} - totalSupply
   * @param {Boolean} - mintable
   * @returns {Promise} resolves with response (success or fail)
   */
  function issueToken($senderAddress, $tokenName, $symbol, $totalSupply = 0, $mintable = false) {
    if (!$senderAddress) {
        throw new Exception("sender address cannot be empty");
    }

    if (strlen($tokenName) > 32) {
      throw new Exception("token name is limited to 32 characters");
    }

    if ($totalSupply <= 0 || $totalSupply > MAXTOTALSUPPLY) {
      throw new Exception("invalid supply amount");
    }

    //$totalSupply = strval(BigDecimal::of($totalSupply));
    $totalSupply = strval(BigDecimal::of($totalSupply)->multipliedBy(BASENUMBER));

    $address = new Address();
    $accCode = $address->DecodeAddress($senderAddress);

    $issueMsg = (object)(array('from' => $accCode, 
        'name' => $tokenName,
        'symbol' => $symbol,
        'total_supply' => $totalSupply,
        'mintable' => $mintable,
        'msgType' => "IssueMsg"
    ));

    $signIssueMsg = (object)(array('from' => $senderAddress, 
        'name' => $tokenName,
        'symbol' => $symbol,
        'total_supply' => (int)$totalSupply,
        'mintable' => $mintable
    ));

    return ($this->_bncClient->_prepareTransaction($issueMsg, $signIssueMsg, $senderAddress));
  }

  /**
   * burn some amount of token
   * @param {String} fromAddress
   * @param {String} symbol
   * @param {Number} amount
   * @returns {Promise}  resolves with response (success or fail)
   */
  function burnToken($fromAddress, $symbol, $amount) {

    $amount = strval(BigDecimal::of($amount)->multipliedBy(BASENUMBER));

    $address = new Address();
    $accCode = $address->DecodeAddress($fromAddress);

    $burnMsg = (object)(array('from' => $accCode, 
        'symbol' => $symbol,
        'amount' => (int)$amount,
        'msgType' => "BurnMsg"
    ));

    $burnSignMsg = (object)(array(
        'amount' => (int)$amount,
        'from' => $fromAddress, 
        'symbol' => $symbol
    ));

    return ($this->_bncClient->_prepareTransaction($burnMsg, $burnSignMsg, $fromAddress));
  }

  /**
   * mint tokens for an existing token
   * @param {String} fromAddress
   * @param {String} symbol
   * @param {Number} amount
   * @returns {Promise}  resolves with response (success or fail)
   */
  function mintToken($fromAddress, $symbol, $amount) {

    if ($amount <= 0 || $amount > MAXTOTALSUPPLY) {
      throw new Exception("invalid amount");
    }

    $amount = strval(BigDecimal::of($amount)->multipliedBy(BASENUMBER));

    $address = new Address();
    $accCode = $address->DecodeAddress($fromAddress);

    $mintMsg = (object)(array('from' => $accCode, 
        'symbol' => $symbol,
        'amount' => (int)$amount,
        'msgType' => "MintMsg"
    ));

    $mintSignMsg = (object)(array(
        'amount' => (int)$amount,
        'from' => $fromAddress, 
        'symbol' => $symbol
    ));

    return ($this->_bncClient->_prepareTransaction($mintMsg, $mintSignMsg, $fromAddress));
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