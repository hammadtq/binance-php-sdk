<?php

namespace Binance\Tx;

use Binance\Encoder\Encoder;
use Binance\Crypto\Keystore;
use Binance\StdTx;
use Binance\Send;
use Binance\Send_Token;
use Binance\Send\Input;
use Binance\Send\Output;
use Binance\StdSignature\PubKey;
use Binance\StdSignature;
use Google\Protobuf\Internal\CodedOutputStream;
use Binance\NewOrder;
use Binance\CancelOrder;
use Binance\TokenFreeze;
use Binance\TokenUnFreeze;
use Binance\Issue;
use Binance\Mint;
use Binance\Burn;
use Binance\SubmitProposal;
use Binance\Deposit;
use Binance\Vote;
use Binance\HashTimerLockTransferMsg;
use Binance\Token;
use Binance\DepositHashTimerLockMsg;
use Binance\ClaimHashTimerLockMsg;
use Binance\RefundHashTimerLockMsg;
use Binance\TimeLock;
use Binance\TimeLock_Token;
use Binance\TimeReLock;
use Binance\TimeReLock_Token;
use Binance\TimeUnLock;
use Binance\PBList;
use Binance\SetAccountFlag;

/**
 * Creates a new transaction object.
 * @example
 * var rawTx = {
 *   account_number: 1,
 *   chain_id: 'bnbchain-1000',
 *   memo: '',
 *   msg: {},
 *   type: 'NewOrderMsg',
 *   sequence: 29,
 *   source: 0
 * };
 * var tx = new Transaction(rawTx);
 * @property {Buffer} raw The raw vstruct encoded transaction
 * @param {Number} data.account_number account number
 * @param {String} data.chain_id bnbChain Id
 * @param {String} data.memo transaction memo
 * @param {String} type transaction type
 * @param {Object} data.msg object data of tx type
 * @param {Number} data.sequence transaction counts
 * @param {Number} data.source where does this transaction come from
 */
class Transaction {

    private $typePrefixes;
    
    function __construct($data) {
        $this->type = $data->type;
        $this->sequence = $data->sequence ?? 0;
        $this->account_number = $data->account_number ?? 0;
        $this->chain_id = $data->chain_id;
        $this->msgs = $data->msg ? [$data->msg] : [];
        $this->memo = $data->memo;
        $this->source = $data->source ?? 0; // default value is 0
        $this->typePrefixes = array(
            'MsgSend' => "2A2C87FA",
            'NewOrderMsg' => "CE6DC043",
            'CancelOrderMsg' => "166E681B",
            'IssueMsg' => "17EFAB80",
            'BurnMsg' => "7ED2D2A0",
            'FreezeMsg' => "E774B32D",
            'UnfreezeMsg' => "6515FF0D",
            'MintMsg' => "467E0829",
            'ListMsg' => "B41DE13F",
            'StdTx' => "F0625DEE",
            'PubKeySecp256k1' => "EB5AE987",
            'SignatureSecp256k1' => "7FC4A495",
            'MsgSubmitProposal' => "B42D614E",
            'MsgDeposit' => "A18A56E5",
            'MsgVote' => "A1CADD36",
            'TimeLockMsg' => "07921531",
            'TimeUnlockMsg' => "C4050C6C",
            'TimeRelockMsg' => "504711DA",
            'HTLTMsg' => "B33F9A24",
            'DepositHTLTMsg' => "63986496",
            'ClaimHTLTMsg' => "C1665300",
            'RefundHTLTMsg' => "3454A27C",
            'SetAccountFlagsMsg' => "BEA6E301"
        );
    }

    /**
   * generate the sign bytes for a transaction, given a msg
   * @param {Object} concrete msg object
   * @return {Buffer}
   **/
    function getSignBytes($msg) {
        if (!$msg) {
            throw new Exception("msg should be an object");
        }

        $signMsg = (object)(array('account_number' => strval($this->account_number), 'chain_id' => $this->chain_id, 'data' => null, 'memo' => $this->memo, 'msgs' => [$msg], 'sequence' => strval($this->sequence), 'source' => strval($this->source)));

        $encoder = new Encoder();
        return $encoder->convertObjectToSignBytes($signMsg);
    }

    /**
     * attaches a signature to the transaction
     * @param {Elliptic.PublicKey} pubKey
     * @param {Buffer} signature
     * @return {Transaction}
     **/
    function addSignature($pubKey, $signature) {
        $pubKey = $this->_serializePubKey($pubKey); // => Buffer
        $this->signatures = array(array('pub_key' => $pubKey, 'signature' => $signature, 'account_number' => $this->account_number, 'sequence' => $this->sequence));
        return $this;
    }

    /**
     * encode signed transfer transaction to hex which is compatible with amino
     */
    function serializeTransfer() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }
        
        $token = new Send_Token();
        $token->setDenom($this->msgs[0]->inputs['coins'][0]->denom); 
        $token->setAmount($this->msgs[0]->inputs['coins'][0]->amount); 

        $input = new Input();
        $input->setAddress(hex2bin($this->msgs[0]->inputs['address']));
        $input->setCoins([$token]);

        $output = new Output();
        $output->setAddress(hex2bin($this->msgs[0]->outputs['address']));
        $output->setCoins([$token]);

        $msgSend = new Send();
        $msgSend->setInputs([$input]);
        $msgSend->setOutputs([$output]);  
        
        $msgToSet = $msgSend->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['MsgSend'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode signed new order transaction to hex which is compatible with amino
     */
    function serializeNewOrder() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $newOrder = new NewOrder();
        $newOrder->setSender(hex2bin($this->msgs[0]->sender));
        $newOrder->setId($this->msgs[0]->id);
        $newOrder->setSymbol($this->msgs[0]->symbol);
        $newOrder->setOrdertype($this->msgs[0]->ordertype);
        $newOrder->setSide($this->msgs[0]->side);
        $newOrder->setPrice($this->msgs[0]->price);
        $newOrder->setQuantity($this->msgs[0]->quantity);
        $newOrder->setTimeinforce($this->msgs[0]->timeinforce);

        $msgToSet = $newOrder->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['NewOrderMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode cancel order transaction to hex which is compatible with amino
     */
    function serializeCancelOrder() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $cancelOrder = new CancelOrder();
        $cancelOrder->setRefId($this->msgs[0]->refid);
        $cancelOrder->setSymbol($this->msgs[0]->symbol);
        $cancelOrder->setSender(hex2bin($this->msgs[0]->sender));

        $msgToSet = $cancelOrder->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['CancelOrderMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode freeze transaction to hex which is compatible with amino
     */
    function serializeTokenFreeze() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $tokenFreeze = new TokenFreeze();
        $tokenFreeze->setAmount($this->msgs[0]->amount);
        $tokenFreeze->setFrom(hex2bin($this->msgs[0]->from));
        $tokenFreeze->setSymbol($this->msgs[0]->symbol);

        $msgToSet = $tokenFreeze->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['FreezeMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode unfreeze transaction to hex which is compatible with amino
     */
    function serializeTokenUnFreeze() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $tokenUnFreeze = new TokenUnFreeze();
        $tokenUnFreeze->setAmount($this->msgs[0]->amount);
        $tokenUnFreeze->setFrom(hex2bin($this->msgs[0]->from));
        $tokenUnFreeze->setSymbol($this->msgs[0]->symbol);

        $msgToSet = $tokenUnFreeze->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['UnfreezeMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode issue token transaction to hex which is compatible with amino
     */
    function serializeIssueToken() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $issue = new Issue();
        $issue->setFrom(hex2bin($this->msgs[0]->from));
        $issue->setName($this->msgs[0]->name);
        $issue->setSymbol($this->msgs[0]->symbol);
        $issue->setTotalSupply($this->msgs[0]->total_supply);
        $issue->setMintable($this->msgs[0]->mintable);

        $msgToSet = $issue->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['IssueMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode burn transaction to hex which is compatible with amino
     */
    function serializeBurnToken() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $burn = new Burn();
        $burn->setFrom(hex2bin($this->msgs[0]->from));
        $burn->setSymbol($this->msgs[0]->symbol);
        $burn->setAmount($this->msgs[0]->amount);

        $msgToSet = $burn->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['BurnMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }


    /**
     * encode mint transaction to hex which is compatible with amino
     */
    function serializeMintToken() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $mint = new Mint();
        $mint->setFrom(hex2bin($this->msgs[0]->from));
        $mint->setSymbol($this->msgs[0]->symbol);
        $mint->setAmount($this->msgs[0]->amount);

        $msgToSet = $mint->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['MintMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode submitproposal transaction to hex which is compatible with amino
     */
    function serializeSubmitProposal() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $submit = new SubmitProposal();
        $submit->setTitle($this->msgs[0]->title);
        $submit->setDescription($this->msgs[0]->description);
        $submit->setProposalType($this->msgs[0]->proposal_type);
        $submit->setProposer(hex2bin($this->msgs[0]->proposer));
        $token = new Token();
        $token->setDenom($this->msgs[0]->initial_deposit->denom); 
        $token->setAmount($this->msgs[0]->initial_deposit->amount);
        $submit->setInitialDeposit([$token]);
        $submit->setVotingPeriod($this->msgs[0]->voting_period);

        $msgToSet = $submit->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['MsgSubmitProposal'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode serialize deposit transaction to hex which is compatible with amino
     */
    function serializeDeposit() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $deposit = new Deposit();
        $deposit->setProposalID($this->msgs[0]->proposal_id);
        $deposit->setDepositer(hex2bin($this->msgs[0]->depositer));
        $token = new Token();
        $token->setDenom($this->msgs[0]->amount["denom"]); 
        $token->setAmount($this->msgs[0]->amount["amount"]);
        $deposit->setAmount([$token]);

        $msgToSet = $deposit->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['MsgDeposit'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }


    /**
     * encode serialize vote transaction to hex which is compatible with amino
     */
    function serializeVote() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $vote = new Vote();
        $vote->setProposalID($this->msgs[0]->proposal_id);
        $vote->setVoter(hex2bin($this->msgs[0]->voter));
        $vote->setOption($this->msgs[0]->option);

        $msgToSet = $vote->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['MsgVote'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode serialize htlt transaction to hex which is compatible with amino
     */
    function serializeHTLT() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $htlt = new HashTimerLockTransferMsg();
        $htlt->setFrom(hex2bin($this->msgs[0]->from));
        $htlt->setTo(hex2bin($this->msgs[0]->to));
        $htlt->setRecipientOtherChain(hex2bin($this->msgs[0]->recipient_other_chain));
        $htlt->setSenderOtherChain(hex2bin($this->msgs[0]->sender_other_chain));
        $htlt->setRandomNumberHash(hex2bin($this->msgs[0]->random_number_hash));
        $htlt->setTimeStamp($this->msgs[0]->timestamp);
        $token = new Token();
        $token->setDenom($this->msgs[0]->amount["denom"]); 
        $token->setAmount($this->msgs[0]->amount["amount"]);
        $htlt->setAmount([$token]);
        $htlt->setExpectedIncome($this->msgs[0]->expected_income);
        $htlt->setHeightSpan($this->msgs[0]->height_span);
        $htlt->setCrossChain($this->msgs[0]->cross_chain);


        $msgToSet = $htlt->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['HTLTMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode serialize deposit htlt transaction to hex which is compatible with amino
     */
    function serializeDepositHTLT() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $deposit = new DepositHashTimerLockMsg();
        $deposit->setFrom(hex2bin($this->msgs[0]->from));
        $deposit->setSwapId(hex2bin($this->msgs[0]->swap_id));
        $token = new Token();
        $token->setDenom($this->msgs[0]->amount["denom"]); 
        $token->setAmount($this->msgs[0]->amount["amount"]);
        $deposit->setAmount([$token]);

        $msgToSet = $deposit->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['DepositHTLTMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode serialize deposit htlt transaction to hex which is compatible with amino
     */
    function serializeClaimHTLT() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $claim = new ClaimHashTimerLockMsg();
        $claim->setFrom(hex2bin($this->msgs[0]->from));
        $claim->setSwapId(hex2bin($this->msgs[0]->swap_id));
        $claim->setRandomNumber(hex2bin($this->msgs[0]->random_number));


        $msgToSet = $claim->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['ClaimHTLTMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode serialize deposit htlt transaction to hex which is compatible with amino
     */
    function serializeRefundHTLT() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $refund = new RefundHashTimerLockMsg();
        $refund->setFrom(hex2bin($this->msgs[0]->from));
        $refund->setSwapId(hex2bin($this->msgs[0]->swap_id));

        $msgToSet = $refund->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['RefundHTLTMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode time lock transaction to hex which is compatible with amino
     */
    function serializeTimeLock() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $lock = new TimeLock();
        $lock->setFrom(hex2bin($this->msgs[0]->from));
        $lock->setDescription($this->msgs[0]->description);
        $token = new TimeLock_Token();
        $token->setDenom($this->msgs[0]->amount["denom"]); 
        $token->setAmount($this->msgs[0]->amount["amount"]);
        $lock->setAmount([$token]);
        $lock->setLockTime($this->msgs[0]->lock_time);

        $msgToSet = $lock->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['TimeLockMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode time relock transaction to hex which is compatible with amino
     */
    function serializeTimeRelock() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $relock = new TimeReLock();
        $relock->setFrom(hex2bin($this->msgs[0]->from));
        $relock->setTimeLockId($this->msgs[0]->time_lock_id);
        $relock->setDescription($this->msgs[0]->description);
        $token = new TimeReLock_Token();
        $token->setDenom($this->msgs[0]->amount["denom"]); 
        $token->setAmount($this->msgs[0]->amount["amount"]);
        $relock->setAmount([$token]);
        $relock->setLockTime($this->msgs[0]->lock_time);

        $msgToSet = $relock->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['TimeRelockMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode time unlock transaction to hex which is compatible with amino
     */
    function serializeTimeUnlock() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $unlock = new TimeUnLock();
        $unlock->setFrom(hex2bin($this->msgs[0]->from));
        $unlock->setTimeLockId($this->msgs[0]->time_lock_id);

        $msgToSet = $unlock->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['TimeUnlockMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode list transaction to hex which is compatible with amino
     */
    function serializeList() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $list = new PBList();
        $list->setFrom(hex2bin($this->msgs[0]->from));
        $list->setProposalId($this->msgs[0]->proposal_id);
        $list->setBaseAssetSymbol($this->msgs[0]->base_asset_symbol);
        $list->setQuoteAssetSymbol($this->msgs[0]->quote_asset_symbol);
        $list->setInitPrice($this->msgs[0]->init_price);

        $msgToSet = $list->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['ListMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode list transaction to hex which is compatible with amino
     */
    function serializeSetAccountFlags() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $flag = new SetAccountFlag();
        $flag->setFrom(hex2bin($this->msgs[0]->from));
        $flag->setFlags($this->msgs[0]->flags);

        $msgToSet = $flag->serializeToString();
        $msgToSetPrefixed = hex2bin($this->typePrefixes['SetAccountFlagsMsg'].bin2hex($msgToSet));
        $signatureToSet = $this->serializeSign();
        return ($this->serializeStdTx($msgToSetPrefixed, $signatureToSet));
    }

    /**
     * encode signatures in amino comaptible format
     */
    function serializeSign(){
        $stdSignature = new StdSignature();
        $stdSignature->setPubKey($this->signatures[0]['pub_key']);
        $stdSignature->setSignature($this->signatures[0]['signature']);
        $stdSignature->setAccountNumber($this->signatures[0]['account_number']);
        $stdSignature->setSequence($this->signatures[0]['sequence']);

        $signatureToSet = $stdSignature->serializeToString();
        return $signatureToSet;
    }

    /**
     * encode wrap message in StdTX amino comaptible format
     */
    function serializeStdTx($msgToSetPrefixed, $signatureToSet){
        $stdTx = new StdTx();
        $stdTx->setMsgs([$msgToSetPrefixed]);
        $stdTx->setSignatures([$signatureToSet]);
        $stdTx->setMemo($this->memo);
        $stdTx->setSource($this->source);
        $stdTx->setData("");
       
        $stdTxBytes = $stdTx->serializeToString();

        $txWithPrefix = $this->typePrefixes['StdTx'].bin2hex($stdTxBytes);
        $lengthPrefix = strlen(pack('H*', $txWithPrefix));
        $output = new CodedOutputStream(2);
        $output->writeVarint64($lengthPrefix);
        $codedVarInt = $output->getData();
        $txToPost = bin2hex($codedVarInt).$txWithPrefix;
        return $txToPost;
    }

    /**
     * sign transaction with a given private key and msg
     * @param {string} privateKey private key hex string
     * @param {Object} concrete msg object
     * @return {Transaction}
     **/
    function sign($privateKey, $msg) {
        if(!$privateKey){
            throw new Exception("private key should not be null");
        }

        if(!$msg){
            throw new Exception("signing message should not be null");
        }

        $signBytes = $this->getSignBytes($msg);

        $privateKeyHex = $privateKey->getHex();

        $context = secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);

        $msg32 = hash('sha256', $signBytes, true);
        $privateKeySt = pack("H*", $privateKeyHex);

        /** @var resource $signature */
        $signature = null;
        if (1 !== secp256k1_ecdsa_sign($context, $signature, $msg32, $privateKeySt)) {
            throw new \Exception("Failed to create signature");
        }
        
        $serialized = '';
        secp256k1_ecdsa_signature_serialize_compact($context, $serialized, $signature);
    
        $keystore = new Keystore();
        
        $this->addSignature($keystore->privateKeyToPublicKey($privateKey), $serialized);
        return $this;
    }

    function _serializePubKey($pubKey){
        $hex = $pubKey -> getHex();
        $lengthPrefix = strlen(pack('H*', $hex));
        // prefix - length of the public key - public key
        $encodedPubKey = hex2bin('eb5ae987'.dechex($lengthPrefix).$hex);
        return $encodedPubKey;
    }

}

?>