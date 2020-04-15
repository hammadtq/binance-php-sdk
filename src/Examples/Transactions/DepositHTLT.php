<?php

namespace Binance\Examples\Transactions;

require '../../../vendor/autoload.php';

use Binance\Client\BncClient;
use Binance\Swap\Swap;

$privateKey = 'd84899c0012ebc48b50b9ea93b9c8d911078f11530d08fb271769b7e8124d1ae';
$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');
$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"
$bncClient->initChain();
$bncClient->setPrivateKey($privateKey);

$coins = array("denom" => "BNB", "amount" => 100);

$swapClient = new Swap($bncClient);
$response = $swapClient->depositHTLT("tbnb1upk2usj02frqhhw9c23789vd027awyzyl2mfpg", "00ccd50c9182aeeae64e1fd81362ef32e5140bbb22ad0a851cceb6709483ac5e", $coins);

var_dump($response);
?>