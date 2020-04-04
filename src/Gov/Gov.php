<?php

namespace Binance\Gov;

use Binance\Crypto\Address;

define("BASENUMBER", pow(10,8));

class Gov {

    private $proposalTypeMapping;
    private $voteOption;
    private $voteOptionMapping;
    private $_bncClient;

    function __construct($bncClient) {
        $this->_bncClient = $bncClient;
        
        $this->proposalTypeMapping = array(
            '0x04' => 'ListTradingPair',
            '0x00' => 'Nil',
            '0x01' => 'Text',
            '0x02' => 'ParameterChange',
            '0x03' => 'SoftwareUpgrade',
            '0x05' => 'FeeChange',
            '0x06' => 'CreateValidator',
            '0x07' => 'RemoveValidator');
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
        // const ProposalTypeNil = 0x00
        // const ProposalTypeText = 0x01
        // const ProposalTypeParameterChange = 0x02
        // const ProposalTypeSoftwareUpgrade = 0x03
        // const ProposalTypeListTradingPair = 0x04
        // const ProposalTypeFeeChange = 0x05
        // const ProposalTypeCreateValidator = 0x06
        // const ProposalTypeRemoveValidator = 0x07
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
    // $listTradingPairObj = {
    //   base_asset_symbol: listParams.baseAsset,
    //   quote_asset_symbol: listParams.quoteAsset,
    //   init_price: +(new Big(listParams.initPrice).mul(BASENUMBER).toString()),
    //   description: listParams.description,
    //   expire_time: new Date(listParams.expireTime).toISOString(),
    // }

    $initPrice = strval(BigDecimal::of($listParams.initPrice));
    $expire_time = new DateTime('2011-01-01T15:03:01.012345Z');

    $listTradingPairObj = (object)(array('base_asset_symbol' => $listParams->baseAsset, 
        'quote_asset_symbol' => $listParams->quoteAsset, 
        'init_price' => $initPrice,
        'description' => $listParams->description,
        'expire_time' => $expire_time
    ));

    $description = json_encode(listTradingPairObj);
    //const { address, title, initialDeposit, votingPeriod } = listParams
    $address = $listParams->address;
    $title = $listParams->title;
    $initialDeposit = $listParams->initialDeposit;
    $votingPeriod = $listParams->votingPeriod;
    // return await this.submitProposal(address, title, description,
    //   proposalType.ProposalTypeListTradingPair, initialDeposit, votingPeriod)
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
  function submitProposal($address, $title, $description, $proposalType, $initialDeposit, $votingPeriod) {
    
    $address = new Address();
    $accCode = $address->DecodeAddress($address);

    $amount = strval(BigDecimal::of($initialDeposit)->multipliedBy(BASENUMBER));
    $coins = (object)(array('denom' => 'BNB', 
        'amount' => $amount
    ));
    
    $votingPeriod = strval(BigDecimal::of($initialDeposit)->multipliedBy(pow(10,9)));

    $proposalMsg = (object)(array('title' => $title, 
        'description' => $description, 
        'proposal_type' => $proposalType,
        'proposer' => $accAddress,
        'initial_deposit' => $coins,
        'voting_period' => $votingPeriod,
        'msgType' => 'MsgSubmitProposal',
    ));

    $signMsg = (object)(array('description' => $description, 
        'initial_deposit' => $coins, 
        'proposal_type' => $proposalTypeMapping[$proposalType],
        'proposer' => $address,
        'title' => $title,
        'voting_period' => '.$votingPeriod.'
    ));

    return ($this->$_bncClient->_prepareTransaction($proposalMsg, $signMsg, $address));
  }

  /**
   * Deposit tokens for activing proposal
   * @param {Number} proposalId
   * @param {String} address
   * @param {Array} coins
   * @example
   * var coins = [{
   *   "denom": "BNB",
   *   "amount": 10
   * }]
   */
  function deposit($proposalId, $address, $coins) {
    
    $address = new Address();
    $accCode = $address->DecodeAddress($address);

    checkCoins($coins);

    $amount = array();
    // coins.forEach(coin => {
    //   amount.push({
    //     denom: coin.denom,
    //     amount: +(new Big(coin.amount).mul(BASENUMBER).toString())
    //   })
    // })

    $depositMsg = (object)(array('proposal_id' => $proposalId,
    'depositer' => $accAddress,
    'amount' => $amount,
    'msgType' => "MsgDeposit"
    ));


    // const signMsg = {
    //   amount: amount.map(coin => ({
    //     denom: coin.denom,
    //     amount: String(coin.amount)
    //   })),
    //   depositer: address,
    //   proposal_id: String(proposalId),
    // }

    return ($this->$_bncClient->_prepareTransaction($depositMsg, $signMsg, $address));
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

    // const voteMsg = {
    //   proposal_id: proposalId,
    //   voter: accAddress,
    //   option,
    //   msgType: "MsgVote"
    // }

    $voteMsg = (object)(array('proposal_id' => $proposalId,
    'voter' => $accCode,
    'option' => $option,
    'msgType' => "MsgVote"
    ));

    // const signMsg = {
    //   option: voteOptionMapping[option],
    //   proposal_id: String(proposalId),
    //   voter,
    // }

    $signMsg = (object)(array('option' => $option,
    'proposal_id' => "".$proposalId."",
    'voter' => $voter
    ));

    return ($this->$_bncClient->_prepareTransaction($voteMsg, $signMsg, $voter));
  }
}

}

?>
