<?php

namespace Binance\Client;

use Binance\Crypto\Bech32;
use Binance\Crypto\Address;
use Binance\Utils\ValidateHelper;
use Brick\Math\BigDecimal;
use Binance\Client\AbciRequest;

define("BASENUMBER", pow(10,8));

class BncClient {

    public $server;
    public $account_number;
    public $chainId;

    function __construct($server) {
        $this->server = $server;
    }

    /**
   * Initialize the client with the chain's ID. Asynchronous.
   * @return {Promise}
   */
    function initChain() {
        if (!$this->chainId) {
            // $data = $this->_httpClient.request("get", api.nodeInfo)
            // $this->chainId = data.result.node_info && data.result.node_info.network
            $client = new GuzzleHttp\Client();
            $res = $client->get('https://testnet-dex.binance.org/api/v1/node-info');
            echo $res->getBody()->node_info();

        }
        return this;
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

        $coin = '{denom: '.$asset.', amount: '.$amount.'}';

        $msg = '{
            inputs: [{
              address: '.$accCode.',
              coins: ['.$coin.']
            }],
            outputs: [{
              address: '.$toAccCode.',
              coins: ['.$coin.']
            }],
            msgType: "MsgSend"
          }';

        $signMsg = '{
            inputs: [{
              address: '.$fromAddress.',
              coins: [{
                amount: '.$amount.',
                denom: '.$asset.'
              }]
            }],
            outputs: [{
              address: '.$toAddress.',
              coins: [{
                amount: '.$amount.',
                denom: '.$asset.'
              }]
            }]
          }';
        echo $signMsg; 
        
        $signedTx = $this->_prepareTransaction($msg, $signMsg, $fromAddress, $sequence, $memo);

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
    function _prepareTransaction($msg, $stdSignMsg, $address, $sequence = null, $memo = "") {
        if ((!$this->account_number || ($sequence !== 0 && !$sequence)) && $address) {
            var_dump($sequence);

            $request = new AbciRequest();

            $result = $request->GetAppAccount($this->server, $address);

            var_dump($result->getBase()->getSequence());

            $sequence = $result->getBase()->getSequence();
            $this->account_number = $result->getBase()->getAccountNumber();

            var_dump($this->account_number);
        }

        $options = '{
            account_number: '.$this->account_number.',
            chain_id: this.chainId,
            memo: memo,
            msg,
            sequence: parseInt(sequence),
            source: this._source,
            type: msg.msgType,
        }';

        // const tx = new Transaction(options)
        // return this._signingDelegate.call(this, tx, stdSignMsg)
    }

}