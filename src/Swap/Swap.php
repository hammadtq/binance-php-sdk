<?php

namespace Binance\Swap;

use Binance\Crypto\Address;

class Swap {

    private $_bncClient;

    function __construct($bncClient) {
        $this->_bncClient = $bncClient;
    }

    /**
   * HTLT(Hash timer locked transfer, create an atomic swap)
   * @param {String} from
   * @param {String} recipient
   * @param {String} recipientOtherChain
   * @param {String} senderOtherChain
   * @param {String} randomNumberHash
   * @param {Number} timestamp
   * @param {Array} amount
   * @param {String} expectedIncome
   * @param {Number} heightSpan
   * @param {boolean} crossChain
   * @returns {Promise}  resolves with response (success or fail)
   */
  function HTLT($from, $recipient, $recipientOtherChain, $senderOtherChain, $randomNumberHash, $timestamp, $amount, $expectedIncome, $heightSpan, $crossChain) {

    $address = new Address();
    $fromCode = $address->DecodeAddress($from);
    $recipientCode = $address->DecodeAddress($recipient);

    $htltMsg = (object)(array('from' => $fromCode, 
        'to' => $recipientCode, 
        'recipient_other_chain' => $recipientOtherChain,
        'sender_other_chain' => $senderOtherChain,
        'random_number_hash' => $randomNumberHash,
        'timestamp' => $timestamp,
        'amount' => $amount,
        'expected_income' => $expectedIncome,
        'height_span' => $heightSpan,
        'cross_chain' => $crossChain,
        'msgType' => 'HTLTMsg',
    ));

    $signHTLTMsg = (object)(array('from' => $from, 
        'to' => $recipient, 
        'recipient_other_chain' => $recipientOtherChain,
        'sender_other_chain' => $senderOtherChain,
        'random_number_hash' => $randomNumberHash,
        'timestamp' => $timestamp,
        'amount' => array($amount),
        'expected_income' => $expectedIncome,
        'height_span' => $heightSpan,
        'cross_chain' => $crossChain
    ));

    return ($this->_bncClient->_prepareTransaction($htltMsg, $signHTLTMsg, $from));
  }

  /**
   * depositHTLT(deposit assets to an existing swap)
   * @param {String} from
   * @param {String} swapID
   * @param {Array} amount
   * @returns {Promise}  resolves with response (success or fail)
   */
  function depositHTLT($from, $swapID, $amount) {

    $address = new Address();
    $fromCode = $address->DecodeAddress($from);

    $depositHTLTMsg = (object)(array('from' => $fromCode,
        'amount' => $amount,
        'swap_id' => $swapID,
        'msgType' => 'DepositHTLTMsg',
    ));

    $signDepositHTLTMsg = (object)(array('from' => $from, 
        'swap_id' => $swapID,
        'amount' => array($amount)
    ));

    return ($this->_bncClient->_prepareTransaction($depositHTLTMsg, $signDepositHTLTMsg, $from));
  }

  /**
   * claimHTLT(claim assets from an swap)
   * @param {String} from
   * @param {String} swapID
   * @param {String} randomNumber
   * @returns {Promise}  resolves with response (success or fail)
   */
  function claimHTLT($from, $swapID, $randomNumber) {

    $address = new Address();
    $fromCode = $address->DecodeAddress($from);

    $claimHTLTMsg = (object)(array('from' => $fromCode, 
        'swap_id' => $swapID, 
        'random_number' => $randomNumber,
        'msgType' => 'ClaimHTLTMsg',
    ));

    $signClaimHTLTMsg = (object)(array('from' => $from, 
        'swap_id' => $swapID, 
        'random_number' => $randomNumber
    ));

    return ($this->_bncClient->_prepareTransaction($claimHTLTMsg, $signClaimHTLTMsg, $from));
  }

  /**
   * refundHTLT(refund assets from an swap)
   * @param {String} from
   * @param {String} swapID
   * @returns {Promise}  resolves with response (success or fail)
   */
  function refundHTLT($from, $swapID) {

    $address = new Address();
    $fromCode = $address->DecodeAddress($from);
    
    $refundHTLTMsg = (object)(array('from' => $fromCode, 
        'swap_id' => $swapID, 
        'msgType' => 'RefundHTLTMsg',
    ));

    $signRefundHTLTMsg = (object)(array('from' => $from, 
        'swap_id' => $swapID
    ));

    return ($this->_bncClient->_prepareTransaction($refundHTLTMsg, $signRefundHTLTMsg, $from));
  }

}

?>
