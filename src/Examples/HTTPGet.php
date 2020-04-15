<?php

namespace Binance\Examples;

require '../../vendor/autoload.php';

use Binance\Client\BncClient;

$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');
$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"

// getAccount
$result = $bncClient->getAccount('tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r');
var_dump($result);

//getBalance
//$result = $bncClient->getBalance('tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r');
//var_dump($result);

//getMarkets
//$result = $bncClient->getMarkets(10, 0);
//var_dump($result);

//getTransactions
//$result = $bncClient->getTransactions('tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r', 0);
//var_dump($result);

//getTx
//$result = $bncClient->getTx('1A7AB4819BA8F9FE8123FDC0395F00D54178A00B204BDC4BACDAF0BFE12B6254');
//var_dump($result);

//getTx
// $result = $bncClient->getDepth();
// var_dump($result);

//getOpenOrders
// $result = $bncClient->getOpenOrders('tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r');
// var_dump($result);

//getSwapByID
// $result = $bncClient->getSwapByID('9a20636420a6c7814ca383ec2dc4caa80dd176ec96ee492081b92c4f7caf3326');
// var_dump($result);

//getSwapByCreator
// $result = $bncClient->getSwapByCreator('tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r');
// var_dump($result);

//getSwapByCreator
// $result = $bncClient->getSwapByRecipient('tbnb1upk2usj02frqhhw9c23789vd027awyzyl2mfpg');
// var_dump($result);