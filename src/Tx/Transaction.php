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

}