<?php

namespace Binance\Examples\Transactions;

require '../../../vendor/autoload.php';

use Binance\Client\BncClient;

$privateKey = 'afccb5311dc836f631aa6e86c5f4ff53b4de8580b1bd2f7da79f1cba910e5bff';
$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');
$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"
$bncClient->initChain();
$bncClient->setPrivateKey($privateKey);

$response = $bncClient->newOrder("BNB_USDT.B-B7C", 1, 0.001, 1, 0, 1);
var_dump($response);
?>