<?php

namespace Binance\Examples\Transactions;

require '../../../vendor/autoload.php';

use Binance\Client\BncClient;
use Binance\Gov\Gov;

$privateKey = 'afccb5311dc836f631aa6e86c5f4ff53b4de8580b1bd2f7da79f1cba910e5bff';
$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');
$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"
$bncClient->initChain();
$bncClient->setPrivateKey($privateKey);

$listParams = (object)array("address" => "tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r",
                    "title" => 'New trading pair',
                    "description" => '',
                    "baseAsset"=> 'BNB',
                    "quoteAsset"=> 'TBNB-457',
                    "initPrice" => 1,
                    "initialDeposit" => 20,
                    "expireTime" => time()+300000,
                    "votingPeriod" => 604800 );

$govClient = new Gov($bncClient);
$response = $govClient->submitListProposal($listParams);
var_dump($response);
?>