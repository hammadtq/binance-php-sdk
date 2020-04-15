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
use Binance\Types\Byte;

define("BASENUMBER", pow(10,8));

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
            $httpClient = new HttpClient($this->network);
            $json = $httpClient->GetAsync($this->api['nodeInfo']);
            $this->chainId = $json->node_info->network;
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
  function newOrder($symbol, $side, $price, $quantity, $sequence = null, $timeinforce = 1) {
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
   * get account
   * @param {String} address
   * @return {Promise} resolves with http response
   */
  function getAccount($address) {
    if (!$address) {
      throw new Exception("address should not be falsy");
    }
      
    $httpClient = new HttpClient($this->network);
    $result = $httpClient->GetAsync($this->api['getAccount']."/".$address);
    return $result;
  }

  /**
   * get balances
   * @param {String} address optional address
   * @return {Promise} resolves with http response
   */
  function getBalance($address) {
      $data = $this->getAccount($address);
      return $data->balances;
  }

  /**
   * get markets
   * @param {Number} limit max 1000 is default
   * @param {Number} offset from beggining, default 0
   * @return {Promise} resolves with http response
   */
  function getMarkets($limit = 1000, $offset = 0) {
    $httpClient = new HttpClient($this->network);
    $result = $httpClient->GetAsync($this->api['getMarkets']."?limit=".$limit."&offset=".$offset);
    return $result;
  }

  /**
   * get transactions for an account
   * @param {String} address optional address
   * @param {Number} offset from beggining, default 0
   * @return {Promise} resolves with http response
   */
  function getTransactions($address, $offset = 0) {
    $httpClient = new HttpClient($this->network);
    $result = $httpClient->GetAsync($this->api['getTransactions']."?address=".$address."&offset=".$offset);
    return $result;
  }

  /**
   * get transaction
   * @param {String} hash the transaction hash
   * @return {Promise} resolves with http response
   */
  function getTx($hash) {
    $httpClient = new HttpClient($this->network);
    $result = $httpClient->GetAsync($this->api['getTx']."/".$hash);
    return $result;
  }

  /**
   * get depth for a given market
   * @param {String} symbol the market pair
   * @return {Promise} resolves with http response
   */
  function getDepth($symbol = "BNB_BUSD-BD1") {
    $httpClient = new HttpClient($this->network);
    $result = $httpClient->GetAsync($this->api['getDepth']."?symbol=".$symbol);
    return $result;
  }

  /**
   * get open orders for an address
   * @param {String} address binance address
   * @param {String} symbol binance BEP2 symbol
   * @return {Promise} resolves with http response
   */
  function getOpenOrders($address) {
    $httpClient = new HttpClient($this->network);
    $result = $httpClient->GetAsync($this->api['getOpenOrders']."?address=".$address);
    return $result;
  }

  /**
   * get atomic swap
   * @param {String} swapID: ID of an existing swap
   * @return {Promise} AtomicSwap
   */
  function getSwapByID($swapID) {
    $httpClient = new HttpClient($this->network);
    $result = $httpClient->GetAsync($this->api['getSwaps']."/".$swapID);
    return $result;
  }

  /**
   * query atomic swap list by creator address
   * @param {String} creator: swap creator address
   * @param {Number} offset from beginning, default 0
   * @param {Number} limit, max 1000 is default
   * @return {Promise} Array of AtomicSwap
   */
  function getSwapByCreator($creator, $limit = 100, $offset = 0) {
    $httpClient = new HttpClient($this->network);
    $result = $httpClient->GetAsync($this->api['getSwaps']."?fromAddress=".$creator."&limit=".$limit."&offset=".$offset);
    return $result;
  }

  /**
   * query atomic swap list by recipient address
   * @param {String} recipient: the recipient address of the swap
   * @param {Number} offset from beginning, default 0
   * @param {Number} limit, max 1000 is default
   * @return {Promise} Array of AtomicSwap
   */
  function getSwapByRecipient($recipient, $limit = 100, $offset = 0) {
    $httpClient = new HttpClient($this->network);
    $result = $httpClient->GetAsync($this->api['getSwaps']."?toAddress=".$recipient."&limit=".$limit."&offset=".$offset);
    return $result;
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
        }else if($msg->msgType == "MsgSubmitProposal"){
            $txToPost = $signedTx->serializeSubmitProposal();
        }else if($msg->msgType == "MsgDeposit"){
            $txToPost = $signedTx->serializeDeposit();
        }else if($msg->msgType == "MsgVote"){
            $txToPost = $signedTx->serializeVote();
        }else if($msg->msgType == "HTLTMsg"){
            $txToPost = $signedTx->serializeHTLT();
        }else if($msg->msgType == "DepositHTLTMsg"){
            $txToPost = $signedTx->serializeDepositHTLT();
        }else if($msg->msgType == "ClaimHTLTMsg"){
            $txToPost = $signedTx->serializeClaimHTLT();
        }else if($msg->msgType == "RefundHTLTMsg"){
            $txToPost = $signedTx->serializeRefundHTLT();
        }else if($msg->msgType == "TimeLockMsg"){
            $txToPost = $signedTx->serializeTimeLock();
        }else if($msg->msgType == "TimeRelockMsg"){
            $txToPost = $signedTx->serializeTimeRelock();
        }else if($msg->msgType == "TimeUnlockMsg"){
            $txToPost = $signedTx->serializeTimeUnlock();
        }else if($msg->msgType == "ListMsg"){
            $txToPost = $signedTx->serializeList();
        }else if($msg->msgType == "SetAccountFlagsMsg"){
            $txToPost = $signedTx->serializeSetAccountFlags();
        }
        $httpClient = new HttpClient($this->network);
        $result = $httpClient->Sendpost($this->api['broadcast'], $txToPost, true);
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
        $privateKeyBytes = Byte::init(hex2bin($privateKey));
        if ($privateKeyBytes !== $this->privateKey) {
            $keystore = new Keystore();
            $publicKey = $keystore->privateKeyToPublicKey($privateKeyBytes);
            $address = $keystore->publicKeyToAddress($publicKey, $this->addressPrefix);
            if (!$address) throw new Exception(`address is falsy: ${address}. invalid private key?`);
            if ($address === $this->address) return $this; // safety
            $this->privateKey = $privateKeyBytes;
            $this->address = $address;
        }
        return $this;
    }

}