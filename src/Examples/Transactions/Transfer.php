<?php

namespace Binance\Examples\Transactions;

require '../../../vendor/autoload.php';

use Binance\Crypto\Keystore;
use Binance\Client\BncClient;

$keystoreData = '{"version":1,"id":"9d3deea8-d0c1-4a74-8b61-153bfa8efefa","crypto":{"ciphertext":"c2c0798bacf87ba443c470bca91c1df14bb62b482105832dedb1fa3a03fce5ae","cipherparams":{"iv":"6b38380e0b2a87e9ca21c22673559eaf"},"cipher":"aes-256-ctr","kdf":"pbkdf2","kdfparams":{"dklen":32,"salt":"ed71b92463370f6b1e019052e033fcb51cdb6601f0fe85573ea3a836b3266842","c":262144,"prf":"hmac-sha256"},"mac":"d43550b4849827b89f211c71540d97e104e85e0af4cbf244e7c075320aa39082caed0e2095f518974386fd2f5ef6136ec27f299b801342e9acce3a8456dd04df"}}';
$keystore= new Keystore();
$keystore->RestoreKeyStore($keystoreData, "Abc123456@", "tbnb");
$privateKey = $keystore->getPrivateKey();   
$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');
$bncClient->initChain();
$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"
$bncClient->setPrivateKey($privateKey);

$response = $bncClient->transfer("tbnb1mmehrux6snnuq6cq2gq4396m9lycwzy700l60a", "tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r", 200, "BNB", "3423423");

var_dump($response);
?>