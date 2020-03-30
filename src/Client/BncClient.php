<?php

namespace Binance\Client;

use GuzzleHttp;
use Binance\Crypto\Bech32;
use Binance\Crypto\Address;
use Binance\Crypto\Keystore;
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
    public $NETWORK_PREFIX_MAPPING;
    public $privateKey;
    public $address;

    function __construct($server, $source = 0) {
        $this->server = $server;
        $this->_source = $source;
        $this->NETWORK_PREFIX_MAPPING = array('testnet' => 'tbnb', 'mainnet' => 'bnb'); 
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
        
        $signMsg = array('inputs' => array(array('address' => $fromAddress, 'coins' => array(array('amount'=>(int)$amount, 'denom'=>$asset)))), 'outputs' => array(array('address' => $toAddress, 'coins' => array(array('amount'=>(int)$amount, 'denom'=>$asset)))));
        
        echo "<br/>signmsg</br>";
        print_r(json_encode($signMsg, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
        echo "<p>&nbsp;</p>";
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
        $signedTx = $tx->sign($this->privateKey, $stdSignMsg);
        echo "<br/>signedTx<br/><br/>";
        var_dump($signedTx);
        $signedBz = $signedTx->serialize();
        // return this._signingDelegate.call(this, tx, stdSignMsg)
    }

    /**
     * Sets the client network (testnet or mainnet).
     * @param {String} network Indicate testnet or mainnet
     */
    function chooseNetwork($network) {
        $this->addressPrefix = $this->NETWORK_PREFIX_MAPPING[$network];
        $this->network = $this->NETWORK_PREFIX_MAPPING[$network] ? $network : "testnet";
    }

    /**
     * Sets the client's private key for calls made by this client. Asynchronous.
     * @param {string} privateKey the private key hexstring
     * @param {boolean} localOnly set this to true if you will supply an account_number yourself via `setAccountNumber`. Warning: You must do that if you set this to true!
     * @return {Promise}
     */
    function setPrivateKey($privateKey, $localOnly = false) {
        if ($privateKey !== $this->privateKey) {
            $keystore = new Keystore();
            $publicKey = $keystore->privateKeyToPublicKey($privateKey);
            $address = $keystore->publicKeyToAddress($publicKey, $this->addressPrefix);
            if (!$address) throw new Exception(`address is falsy: ${address}. invalid private key?`);
            if ($address === $this->address) return $this; // safety
            $this->privateKey = $privateKey;
            var_dump($privateKey->getHex());
            $this->address = $address;
            // if (!$localOnly) {
            //     // _setPkPromise is used in _sendTransaction for non-await calls
            //     try {
            //         $promise = $this->_setPkPromise = this._httpClient.request("get", `${api.getAccount}/${address}`);
            //         $data = await promise;
            //         $this->account_number = data.result.account_number;
            //     } catch (e) {
            //         throw new Error(`unable to query the address on the blockchain. try sending it some funds first: ${address}`)
            //     }
            // }
        }
        return $this;
    }

}