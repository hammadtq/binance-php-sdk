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
use Binance\Client\HttpClient;
use Binance\Exception;

define("BASENUMBER", pow(10,8));
define("MAXTOTALSUPPLY", 9000000000000000000);

class BncClient {

    public $server;
    public $account_number;
    public $chainId;
    public $_source;
    public $NETWORK_PREFIX_MAPPING;
    public $privateKey;
    public $address;
    private $api;

    function __construct($server, $source = 0) {
        $this->server = $server;
        $this->_source = $source;
        $this->address = "";
        $this->NETWORK_PREFIX_MAPPING = array('testnet' => 'tbnb', 'mainnet' => 'bnb'); 
        $this->api = array('broadcast' => "/api/v1/broadcast",
            'nodeInfo' => "/api/v1/node-info",
            'getAccount' => "/api/v1/account",
            'getMarkets' => "/api/v1/markets",
            'getSwaps' => "/api/v1/atomic-swaps",
            'getOpenOrders' => "/api/v1/orders/open",
            'getDepth' => "/api/v1/depth",
            'getTransactions' => "/api/v1/transactions",
            'getTx' => "/api/v1/tx");
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

        $coin = (object)array('denom' => $asset, 'amount' => $amount);

        $msg = (object)(array('inputs' => array('address' => $accCode, 'coins' => [$coin]), 'outputs' => array('address' => $toAccCode, 'coins' => [$coin]), 'msgType' => 'MsgSend'));
        
        $signMsg = array('inputs' => array(array('address' => $fromAddress, 'coins' => array(array('amount'=>(int)$amount, 'denom'=>$asset)))), 'outputs' => array(array('address' => $toAddress, 'coins' => array(array('amount'=>(int)$amount, 'denom'=>$asset)))));
        
        return($this->_prepareTransaction($msg, $signMsg, $fromAddress, $sequence, $memo, "MsgSend"));
    }

    /**
   * Place new order.
   * @param {String} address
   * @param {String} symbol the market pair
   * @param {Number} side (1-Buy, 2-Sell)
   * @param {Number} price
   * @param {Number} quantity
   * @param {Number} sequence optional sequence
   * @param {Number} timeinforce (1-GTC(Good Till Expire), 3-IOC(Immediate or Cancel))
   * @return {Promise} resolves with response (success or fail)
   */
  function NewOrder($symbol, $side, $price, $quantity, $sequence = null, $timeinforce = 1) {
    if (!$this->address) {
      throw new Exception("address should not be falsy");
    }
    if (!$symbol) {
      throw new Exception("symbol should not be falsy");
    }
    if ($side !== 1 && $side !== 2) {
      throw new Exception("side can only be 1 or 2");
    }
    if ($timeinforce !== 1 && $timeinforce !== 3) {
      throw new Exception("timeinforce can only be 1 or 3");
    }

    $address = new Address();
    $accCode = $address->DecodeAddress($this->address);

    if ($sequence == 0 || !$sequence) {
        $request = new AbciRequest();
        $result = $request->GetAppAccount($this->server, $this->address);
        $sequence = $result->getBase()->getSequence();
    }

    $bigPrice = strval(BigDecimal::of($price));
    $bigQuantity = strval(BigDecimal::of($quantity));

    $sequence = $sequence+1;
    $id = strtoupper($accCode.'-'.$sequence);

    $NewOrderMsg = (object)(array('sender' => $accCode, 
        'id' => $id, 
        'symbol' => $symbol,
        'ordertype' => 2,
        'side' => $side,
        'price' =>  (int)strval(BigDecimal::of($bigPrice)->multipliedBy(BASENUMBER)),
        'quantity' => (int)strval(BigDecimal::of($bigQuantity)->multipliedBy(BASENUMBER)),
        'timeinforce' => $timeinforce,
        'msgType' => "NewOrderMsg"
    ));

    $signMsg = (object)(array('id' => $NewOrderMsg->id,
        'ordertype' => $NewOrderMsg->ordertype,
        'price' => $NewOrderMsg->price,
        'quantity' => $NewOrderMsg->quantity,
        'sender' => $this->address,
        'side' => $NewOrderMsg->side,
        'symbol' => $NewOrderMsg->symbol,
        'timeinforce' => $timeinforce
    ));

    $validateHelper = new ValidateHelper();
    $validateHelper->checkNumber($NewOrderMsg->price, "price");
    $validateHelper->checkNumber($NewOrderMsg->quantity, "quantity");

    return ($this->_prepareTransaction($NewOrderMsg, $signMsg, $this->address, $sequence, ""));
  }

  /**
   * Cancel an order.
   * @param {String} symbol the market pair
   * @param {String} refid the order ID of the order to cancel
   * @param {Number} sequence optional sequence
   * @return {Promise} resolves with response (success or fail)
   */
  function cancelOrder($symbol, $refid, $sequence = null) {
    $address = new Address();
    $accCode = $address->DecodeAddress($this->address);

    $CancelOrderMsg = (object)(array('sender' => $accCode, 
        'symbol' => $symbol,
        'refid' => $refid,
        'msgType' => "CancelOrderMsg"
    ));

    $signMsg = (object)(array('refid' => $refid, 
        'sender' => $this->address,
        'symbol' => $symbol
    ));

    return ($this->_prepareTransaction($CancelOrderMsg, $signMsg, $this->address, $sequence, ""));
  }

  /**
   * Freeze a token.
   * @param {String} symbol the market pair
   * @param {String} refid the order ID of the order to cancel
   * @param {Number} sequence optional sequence
   * @return {Promise} resolves with response (success or fail)
   */
  function tokenFreeze($symbol, $amount, $sequence = null) {
    $address = new Address();
    $accCode = $address->DecodeAddress($this->address);

    $amount = strval(BigDecimal::of($amount)->multipliedBy(BASENUMBER));

    $TokenFreeze = (object)(array('from' => $accCode, 
        'symbol' => $symbol,
        'amount' => (int)$amount,
        'msgType' => "FreezeMsg"
    ));

    $signMsg = (object)(array('amount' => (int)$amount, 
        'from' => $this->address,
        'symbol' => $symbol
    ));

    return ($this->_prepareTransaction($TokenFreeze, $signMsg, $this->address, $sequence, ""));
  }

  /**
   * UnFreeze a token.
   * @param {String} symbol the market pair
   * @param {String} refid the order ID of the order to cancel
   * @param {Number} sequence optional sequence
   * @return {Promise} resolves with response (success or fail)
   */
  function tokenUnFreeze($symbol, $amount, $sequence = null) {
    $address = new Address();
    $accCode = $address->DecodeAddress($this->address);

    $amount = strval(BigDecimal::of($amount)->multipliedBy(BASENUMBER));

    $TokenUnFreeze = (object)(array('from' => $accCode, 
        'symbol' => $symbol,
        'amount' => (int)$amount,
        'msgType' => "UnFreezeMsg"
    ));

    $signMsg = (object)(array('amount' => (int)$amount, 
        'from' => $this->address,
        'symbol' => $symbol
    ));

    return ($this->_prepareTransaction($TokenUnFreeze, $signMsg, $this->address, $sequence, ""));
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

    $totalSupply = strval(BigDecimal::of($totalSupply));
    $totalSupply = strval(BigDecimal::of($totalSupply)->multipliedBy(BASENUMBER));

    $address = new Address();
    $accCode = $address->DecodeAddress($senderAddress);

    $issueMsg = (object)(array('from' => $accCode, 
        'name' => $tokenName,
        'symbol' => $symbol,
        'total_supply' => (int)$totalSupply,
        'mintable' => $mintable,
        'msgType' => "IssueMsg"
    ));

    $signIssueMsg = (object)(array('from' => $accCode, 
        'name' => $tokenName,
        'symbol' => $symbol,
        'total_supply' => (int)$totalSupply,
        'mintable' => $mintable
    ));

    return ($this->_prepareTransaction($issueMsg, $signIssueMsg, $senderAddress));
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
        'from' => $accCode, 
        'symbol' => $symbol
    ));

    return ($this->_prepareTransaction($burnMsg, $burnSignMsg, $fromAddress));
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
        'from' => $accCode, 
        'symbol' => $symbol
    ));

    return ($this->_prepareTransaction($mintMsg, $mintSignMsg, $fromAddress));
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

        $options = (object)array('account_number' => $this->account_number, 'chain_id' => $this->chainId, 'memo' => $memo, 'msg' => $msg, 'sequence' => $sequence, 'source' => $this->_source, 'type' => $msg->msgType);

        $tx = new Transaction($options);
        $signedTx = $tx->sign($this->privateKey, $stdSignMsg);
        
        if ($msg->msgType == "MsgSend"){
            $txToPost = $signedTx->serializeTransfer();
        }else if($msg->msgType == "NewOrderMsg"){
            $txToPost = $signedTx->serializeNewOrder();
        }else if($msg->msgType == "CancelOrderMsg"){
            $txToPost = $signedTx->serializeCancelOrder();
        }else if($msg->msgType == "FreezeMsg"){
            $txToPost = $signedTx->serializeTokenFreeze();
        }else if($msg->msgType == "UnFreezeMsg"){
            $txToPost = $signedTx->serializeTokenUnFreeze();
        }else if($msg->msgType == "IssueMsg"){
            $txToPost = $signedTx->serializeIssueToken();
        }else if($msg->msgType == "BurnMsg"){
            $txToPost = $signedTx->serializeBurnToken();
        }else if($msg->msgType == "MintMsg"){
            $txToPost = $signedTx->serializeMintToken();
        }
        $httpClient = new HttpClient($this->network);
        $result = $httpClient->Sendpost($this->api['broadcast'], $txToPost);
        return $result;
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