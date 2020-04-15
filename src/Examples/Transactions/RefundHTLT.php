<?php

namespace Binance\Examples\Transactions;

require '../../../vendor/autoload.php';

use Binance\Client\BncClient;
use Binance\Swap\Swap;

$privateKey = 'afccb5311dc836f631aa6e86c5f4ff53b4de8580b1bd2f7da79f1cba910e5bff';
$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');
$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"
$bncClient->initChain();
$bncClient->setPrivateKey($privateKey);

$swapClient = new Swap($bncClient);
$response = $swapClient->refundHTLT("tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r", "9a20636420a6c7814ca383ec2dc4caa80dd176ec96ee492081b92c4f7caf3326");

var_dump($response);
?>