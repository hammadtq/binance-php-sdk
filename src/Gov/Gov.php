<?php

namespace Binance\Gov;

use Binance\Crypto\Address;
use Brick\Math\BigDecimal;
use Binance\Exception;

class Gov {

    private $proposalTypeMapping;
    private $voteOption;
    private $voteOptionMapping;
    private $_bncClient;
    private $proposalType;

    function __construct($bncClient) {
        $this->_bncClient = $bncClient;

        $this->proposalType = (object)array(
            'ProposalTypeNil' => 0x00,
            'ProposalTypeText' => 0x01,
            'ProposalTypeParameterChange' => 0x02,
            'ProposalTypeSoftwareUpgrade' => 0x03,
            'ProposalTypeListTradingPair' => 0x04,
            'ProposalTypeFeeChange' => 0x05,
            'ProposalTypeCreateValidator' => 0x06,
            'ProposalTypeRemoveValidator' => 0x07,
        );
        
        $this->proposalTypeMapping = array(
            0x04 => 'ListTradingPair',
            0x00 => 'Nil',
            0x01 => 'Text',
            0x02 => 'ParameterChange',
            0x03 => 'SoftwareUpgrade',
            0x05 => 'FeeChange',
            0x06 => 'CreateValidator',
            0x07 => 'RemoveValidator');
        $this->voteOption = array(
            'OptionEmpty' => 0x00,
            'OptionYes' => 0x01,
            'OptionAbstain' => 0x02,
            'OptionNo' => 0x03,
            'OptionNoWithVeto' => 0x04
        );
        $this->voteOption = array(
            0x00 => 'Empty',
            0x01 => 'Yes',
            0x02 => 'Abstain',
            0x03 => 'No',
            0x04 => 'NoWithVeto'
        );
    }

    /**
   * Submit a list proposal along with an initial deposit
   * @param {Object} listParams
   * @example
   * var listParams = {
   *  title: 'New trading pair',
   *  description: '',
   *  baseAsset: 'BTC',
   *  quoteAsset: 'BNB',
   *  initPrice: 1,
   *  address: '',
   *  initialDeposit: 2000,
   *  expireTime: 1570665600,
   *  votingPeriod: 604800
   * }
   */
  function submitListProposal($listParams) {

    $initPrice = strval(BigDecimal::of($listParams->initPrice)->multipliedBy(BASENUMBER));
    $expire_time = gmdate("Y-m-d\TH:i:s\Z", $listParams->expireTime);
    

    $listTradingPairObj = (object)(array('base_asset_symbol' => $listParams->baseAsset, 
        'quote_asset_symbol' => $listParams->quoteAsset, 
        'init_price' => (int)$initPrice,
        'description' => $listParams->description,
        'expire_time' => $expire_time
    ));

    $description = json_encode($listTradingPairObj);
    
    $address = $listParams->address;
    $title = $listParams->title;
    $initialDeposit = $listParams->initialDeposit;
    $votingPeriod = $listParams->votingPeriod;

    return ($this->submitProposal($address, $title, $description, $this->proposalType->ProposalTypeListTradingPair, $initialDeposit, $votingPeriod));
  }

  /**
   * Submit a proposal along with an initial deposit.
   * Proposal title, description, type and deposit can
   * be given directly or through a proposal JSON file.
   * @param {String} address
   * @param {String} title
   * @param {String} description
   * @param {Number} proposalType
   * @param {Number} initialDeposit
   * @param {String} votingPeriod
   * @return {Promise} resolves with response (success or fail)
   */
  function submitProposal($fromAddress, $title, $description, $proposalType, $initialDeposit, $votingPeriod) {
    
    $address = new Address();
    $accCode = $address->DecodeAddress($fromAddress);

    $amount = strval(BigDecimal::of($initialDeposit)->multipliedBy(BASENUMBER));
    
    $coins = (object)(array('amount' => $amount,
        'denom' => 'BNB'
    ));

    $votingPeriod = strval(BigDecimal::of($votingPeriod)->multipliedBy(10**9));


    $proposalMsg = (object)(array('title' => $title, 
        'description' => $description, 
        'proposal_type' => $proposalType,
        'proposer' => $accCode,
        'initial_deposit' => $coins,
        'voting_period' => $votingPeriod,
        'msgType' => 'MsgSubmitProposal',
    ));

    $signMsg = (object)(array(
        'description' => $description, 
        'initial_deposit' => [$coins], 
        'proposal_type' => $this->proposalTypeMapping[$proposalType],
        'proposer' => $fromAddress,
        'title' => $title,
        'voting_period' => $votingPeriod
    ));

    return ($this->_bncClient->_prepareTransaction($proposalMsg, $signMsg, $fromAddress));
  }

  /**
   * Deposit tokens for activing proposal
   * @param {Number} proposalId
   * @param {String} fromAddress
   * @param {Array} coins
   * @example
   * var coins = [{
   *   "denom": "BNB",
   *   "amount": 10
   * }]
   */
  function deposit($proposalId, $fromAddress, $amount) {
    
    $address = new Address();
    $accCode = $address->DecodeAddress($fromAddress);

    $coins = array('denom' => 'BNB',
        'amount' => strval(BigDecimal::of($amount)->multipliedBy(BASENUMBER))
    );

    $depositMsg = (object)(array('proposal_id' => $proposalId,
    'depositer' => $accCode,
    'amount' => $coins,
    'msgType' => "MsgDeposit"
    ));

    $signMsg = (object)(array(
        'amount' => [$coins], 
        'depositer' => $fromAddress,
        'proposal_id' => strval($proposalId)
    ));

    return ($this->_bncClient->_prepareTransaction($depositMsg, $signMsg, $fromAddress));
  }

  /**
   *
   * @param {Number} proposalId
   * @param {String} voter
   * @param {VoteOption} option
   */
  function vote($proposalId, $voter, $option) {
    $address = new Address();
    $accCode = $address->DecodeAddress($voter);

    $voteMsg = (object)(array('proposal_id' => $proposalId,
        'voter' => $accCode,
        'option' => $option,
        'msgType' => "MsgVote"
    ));

    $signMsg = (object)(array('option' => $this->voteOption[$option],
        'proposal_id' => strval($proposalId),
        'voter' => $voter
    ));

    return ($this->_bncClient->_prepareTransaction($voteMsg, $signMsg, $voter));
  }

  /**
   * @param {String} fromAddress
   * @param {Number} proposalId
   * @param {String} baseAsset
   * @param {String} quoteAsset
   * @param {Number} initPrice
   * @param {Number} sequence optional sequence
   * @return {Promise} resolves with response (success or fail)
   */
  function list($fromAddress, $proposalId, $baseAsset, $quoteAsset, $initPrice, $sequence = null) {
    $address = new Address();
    $accCode = $address->DecodeAddress($fromAddress);

    if (!$address) {
      throw new Exception("address should not be falsy");
    }

    if($proposalId <= 0){
      throw new Error("proposal id should larger than 0");
    }

    if($initPrice <= 0){
      throw new Error("price should larger than 0");
    }

    if (!$baseAsset) {
      throw new Error("baseAsset should not be falsy");
    }

    if (!$quoteAsset) {
      throw new Error("quoteAsset should not be falsy");
    }

    $init_price = strval(BigDecimal::of($initPrice)->multipliedBy(BASENUMBER));

    $listMsg = (object)(array('from' => $accCode,
        'proposal_id' => $proposalId,
        'base_asset_symbol' => $baseAsset,
        'quote_asset_symbol' => $quoteAsset,
        'init_price' => $init_price,
        'msgType' => "ListMsg"
    ));

    $signMsg = (object)(array(
        'base_asset_symbol' => $baseAsset,
        'from' => $fromAddress,
        'init_price' => (int)$init_price,
        'proposal_id' => $proposalId,
        'quote_asset_symbol' => $quoteAsset,
    ));

    return ($this->_bncClient->_prepareTransaction($listMsg, $signMsg, $fromAddress));
  }

  /**
   * Set account flags
   * @param {String} fromAddress
   * @param {Number} flags new value of account flags
   * @param {Number} sequence optional sequence
   * @return {Promise} resolves with response (success or fail)
   */
  function setAccountFlags($fromAddress, $flags, $sequence = null) {
    $address = new Address();
    $accCode = $address->DecodeAddress($fromAddress);

    $msg = (object)(array(
        'from' => $accCode,
        'flags' => $flags,
        'msgType' => "SetAccountFlagsMsg"
    ));

    $signMsg = (object)(array(
        'flags' => $flags,
        'from' => $fromAddress
    ));

    return ($this->_bncClient->_prepareTransaction($msg, $signMsg, $fromAddress));
  }
}

?>
