<?php

namespace Binance\Tx;

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
        $this->sequence = $data->sequence || 0;
        $this->account_number = $data->account_number || 0;
        $this->chain_id = $data->chain_id;
        $this->msgs = $data->msg ? [$data->msg] : [];
        $this->memo = $data->memo;
        $this->source = $data->source || 0; // default value is 0
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

        return encoder.convertObjectToSignBytes($signMsg);
    }

    /**
     * attaches a signature to the transaction
     * @param {Elliptic.PublicKey} pubKey
     * @param {Buffer} signature
     * @return {Transaction}
     **/
    function addSignature($pubKey, $signature) {
        $pubKey = $this->_serializePubKey(pubKey); // => Buffer
        // this.signatures = [{
        //     pub_key: pubKey,
        //     signature: signature,
        //     account_number: this.account_number,
        //     sequence: this.sequence,
        // }]
        return $this;
    }

    /**
     * encode signed transaction to hex which is compatible with amino
     * @param {object} opts msg field
     */
    function serialize() {
        if (!this.signatures) {
            throw new Exception("need signature");
        }

        $msg = this.msgs[0];

        // $stdTx = {
        //     msg: [msg],
        //     signatures: this.signatures,
        //     memo: this.memo,
        //     source: this.source, // sdk value is 0, web wallet value is 1
        //     data: "",
        //     msgType: TxTypes.StdTx
        // }

        $bytes = encoder.marshalBinary(stdTx);
        return bytes.toString("hex");
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
        $privKeyBuf = Buffer.from($privateKey, "hex");
        $signature = crypto.generateSignature($signBytes.toString("hex"), $privKeyBuf);
        $this->addSignature(crypto.generatePubKey($privKeyBuf), $signature);
        return $this;
    }

    /**
     * Sets the client's private key for calls made by this client. Asynchronous.
     * @param {string} privateKey the private key hexstring
     * @param {boolean} localOnly set this to true if you will supply an account_number yourself via `setAccountNumber`. Warning: You must do that if you set this to true!
     * @return {Promise}
     */
    function setPrivateKey($privateKey, $localOnly = false) {
        if (privateKey !== this.privateKey) {
            $address = crypto.getAddressFromPrivateKey($privateKey, $this->addressPrefix);
            if (!$address) throw new Exception(`address is falsy: ${address}. invalid private key?`);
            if (address === this.address) return $this; // safety
            $this->privateKey = $privateKey;
            $this->address = $address;
            if (!$localOnly) {
                // _setPkPromise is used in _sendTransaction for non-await calls
                try {
                    $promise = $this->_setPkPromise = this._httpClient.request("get", `${api.getAccount}/${address}`);
                    $data = await promise;
                    $this->account_number = data.result.account_number;
                } catch (e) {
                    throw new Error(`unable to query the address on the blockchain. try sending it some funds first: ${address}`)
                }
            }
        }
        return $this;
    }

}

?>