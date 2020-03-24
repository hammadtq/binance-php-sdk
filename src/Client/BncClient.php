<?php

namespace Binance\Client;

use GuzzleHttp;
use Binance\Crypto\Bech32;
use Binance\Crypto\Address;
use Binance\Utils\ValidateHelper;
use Brick\Math\BigDecimal;
use Binance\Client\AbciRequest;
use Binance\Tx\Transaction;

define("BASENUMBER", pow(10,8));

class BncClient {

    public $server;
    public $account_number;
    public $chainId;
    public $_source;

    function __construct($server, $source = 0) {
        $this->server = $server;
        $this->_source = $source;
    }

    /**
   * Initialize the client with the chain's ID. Asynchronous.
   * @return {Promise}
   */
    function initChain() {
        if (!$this->chainId) {
            $client = new GuzzleHttp\Client();
            $res = $client->get('https://testnet-dex.binance.org/api/v1/node-info');
            $json = json_decode($res->getBody(), true);
            $this->chainId = $json["node_info"]["network"];
        }
        return $this;
    }

    /**
   * Transfer tokens from one address to another.
   * @param {String} fromAddress
   * @param {String} toAddress
   * @param {Number} amount
   * @param {String} asset
   * @param {String} memo optional memo
   * @param {Number} sequence optional sequence
   * @return {Promise} resolves with response (success or fail)
   */
    function transfer($fromAddress, $toAddress, $amount, $asset, $memo = "", $sequence = null) {
        $address = new Address();
        $accCode = $address->DecodeAddress($fromAddress);
        $toAccCode = $address->DecodeAddress($toAddress);
        
        $amount = strval(BigDecimal::of($amount)->multipliedBy(BASENUMBER));

        $validateHelper = new ValidateHelper();
        
        $validateHelper->checkNumber($amount, "amount");

        //$coin = '{denom: '.$asset.', amount: '.$amount.'}';

        $coin = (object)array('denom' => $asset, 'amount' => $amount);

        //var_dump(json_encode($coin));

        // $msg = '{
        //     inputs: [{
        //       address: '.$accCode.',
        //       coins: ['.$coin.']
        //     }],
        //     outputs: [{
        //       address: '.$toAccCode.',
        //       coins: ['.$coin.']
        //     }],
        //     msgType: "MsgSend"
        //   }';

        $msg = (object)(array('inputs' => array('address' => $accCode, 'coins' => [$coin]), 'outputs' => array('address' => $toAccCode, 'coins' => [$coin]), 'msgType' => 'MsgSend'));

        // $signMsg = '{
        //     inputs: [{
        //       address: '.$fromAddress.',
        //       coins: [{
        //         amount: '.$amount.',
        //         denom: '.$asset.'
        //       }]
        //     }],
        //     outputs: [{
        //       address: '.$toAddress.',
        //       coins: [{
        //         amount: '.$amount.',
        //         denom: '.$asset.'
        //       }]
        //     }]
        //   }';
        
        $signMsg = (object)array('inputs' => array('address' => $fromAddress, 'coins' => array('amount'=>$amount, 'denom'=>$asset)), 'outputs' => array('address' => $toAddress, 'coins' => array('amount'=>$amount, 'denom'=>$asset)));
        
        $signedTx = $this->_prepareTransaction($msg, $signMsg, $fromAddress, $sequence, $memo, "MsgSend");

    }

    /**
   * Prepare a serialized raw transaction for sending to the blockchain.
   * @param {Object} msg the msg object
   * @param {Object} stdSignMsg the sign doc object used to generate a signature
   * @param {String} address
   * @param {Number} sequence optional sequence
   * @param {String} memo optional memo
   * @return {Transaction} signed transaction
   */
    function _prepareTransaction($msg, $stdSignMsg, $address, $sequence = null, $memo = "", $msgType = null) {
        if ((!$this->account_number || ($sequence !== 0 && !$sequence)) && $address) {

            $request = new AbciRequest();

            $result = $request->GetAppAccount($this->server, $address);

            $sequence = $result->getBase()->getSequence();
            $this->account_number = $result->getBase()->getAccountNumber();
        }

        // $options = '{
        //     account_number: '.$this->account_number.',
        //     chain_id: '.$this->chainId.',
        //     memo: '.$memo.',
        //     '.json_encode($msg).',
        //     sequence: '.$sequence.',
        //     source: '.$this->_source.',
        //     type: '.$msg->msgType.'
        // }';

        $options = (object)array('account_number' => $this->account_number, 'chain_id' => $this->chainId, 'memo' => $memo, 'msg' => $msg, 'sequence' => $sequence, 'source' => $this->_source, 'type' => $msg->msgType);

        $tx = new Transaction($options);
        var_dump($tx);
        // return this._signingDelegate.call(this, tx, stdSignMsg)
    }

}