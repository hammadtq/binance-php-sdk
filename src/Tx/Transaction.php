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
use GuzzleHttp;
use Google\Protobuf\Internal\CodedOutputStream;
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
    
    function __construct($data) {
        $this->type = $data->type;
        $this->sequence = $data->sequence ?? 0;
        $this->account_number = $data->account_number ?? 0;
        $this->chain_id = $data->chain_id;
        $this->msgs = $data->msg ? [$data->msg] : [];
        $this->memo = $data->memo;
        $this->source = $data->source ?? 0; // default value is 0
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
        // $signMsg = {
        // "account_number": this.account_number.toString(),
        // "chain_id": this.chain_id,
        // "data": null,
        // "memo": this.memo,
        // "msgs": [msg],
        // "sequence": this.sequence.toString(),
        // "source": this.source.toString()
        // }

        $signMsg = (object)(array('account_number' => strval($this->account_number), 'chain_id' => $this->chain_id, 'data' => null, 'memo' => $this->memo, 'msgs' => [$msg], 'sequence' => strval($this->sequence), 'source' => strval($this->source)));

        var_dump($signMsg);
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
        echo "<br>pubkey<br/>";
        var_dump($pubKey);
        // this.signatures = [{
        //     pub_key: pubKey,
        //     signature: signature,
        //     account_number: this.account_number,
        //     sequence: this.sequence,
        // }]
        $this->signatures = array(array('pub_key' => $pubKey, 'signature' => $signature, 'account_number' => $this->account_number, 'sequence' => $this->sequence));
        return $this;
    }

    /**
     * encode signed transaction to hex which is compatible with amino
     * @param {object} opts msg field
     */
    function serialize() {
        if (!$this->signatures) {
            throw new Exception("need signature");
        }

        $msg = $this->msgs[0]->inputs;
        $inputs = $this->msgs[0]->inputs;
        $outputs = $this->msgs[0]->outputs;
        //var_dump($msg);
        $inputsArr = json_decode(json_encode($inputs), true);
        //var_dump($inputsArr);
        echo '<br/>---</br>';
        var_dump([$this->signatures[0]['pub_key']]);
        echo '<br/>---</br>';
       
        $msg = array('inputs' => 'afdsfas', 'address' => '$accCode', 'coins' => '[$coin]');

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
        $msgToSetPrefixed = hex2bin('2A2C87FA'.bin2hex($msgToSet));
        var_dump(bin2hex($msgToSetPrefixed));
        var_dump(bin2hex($this->signatures[0]['signature']));
        //$pubkey = new PubKey([$this->signatures[0]['pub_key']]);
        $stdSignature = new StdSignature();
        $stdSignature->setPubKey($this->signatures[0]['pub_key']);
        $stdSignature->setSignature($this->signatures[0]['signature']);
        $stdSignature->setAccountNumber($this->signatures[0]['account_number']);
        $stdSignature->setSequence($this->signatures[0]['sequence']);

        $signatureToSet = $stdSignature->serializeToString();
        echo "signature to set:";
        var_dump(bin2hex($signatureToSet));

        $stdTx = new StdTx();
        //$existingMsg = $stdTx->getMsgs();
        
        $stdTx->setMsgs([$msgToSetPrefixed]);
        $stdTx->setSignatures([$signatureToSet]);
        $stdTx->setMemo($this->memo);
        $stdTx->setSource($this->source);
        $stdTx->setData("");
        //$stdTx->setMsgType("StdTx");
        $stdTxBytes = $stdTx->serializeToString();
        var_dump(bin2hex($stdTxBytes));
        
        $txWithPrefix = 'F0625DEE'.bin2hex($stdTxBytes);
        var_dump($txWithPrefix);
        $lengthPrefix = strlen(pack('H*', $txWithPrefix));
        $output = new CodedOutputStream(2);
        $output->writeVarint64($lengthPrefix);
        $codedVarInt = $output->getData();
        $txToPost = bin2hex($codedVarInt).$txWithPrefix;
        var_dump($txToPost);
       

            $client = new GuzzleHttp\Client();
            $response = $client->post('https://testnet-dex.binance.org/api/v1/broadcast', [
                'debug' => TRUE,
                'body' => $txToPost,
                'headers' => [
                'Content-Type' => 'text/plain',
                ]
            ]);
            
            $body = $response->getBody();
            print_r(json_decode((string) $body));
        
        // $bytes = encoder.marshalBinary(stdTx);
        // return bytes.toString("hex");
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
        echo "sign_bytes<br/>";
        var_dump($signBytes);

        $privateKeyHex = $privateKey->getHex();


        $context = secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);

        $msg32 = hash('sha256', $signBytes, true);
        $privateKeySt = pack("H*", $privateKeyHex);

        /** @var resource $signature */
        $signature = null;
        if (1 !== secp256k1_ecdsa_sign($context, $signature, $msg32, $privateKeySt)) {
            throw new \Exception("Failed to create signature");
        }
        echo $signature;
        $serialized = '';
        secp256k1_ecdsa_signature_serialize_compact($context, $serialized, $signature);
        echo sprintf("Produced signature: %s \n", bin2hex($serialized));
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