<?php

namespace Binance\Examples;

require '../../vendor/autoload.php';

use Binance\Crypto\Keystore;

$privateKey = 'afccb5311dc836f631aa6e86c5f4ff53b4de8580b1bd2f7da79f1cba910e5bff';
$keystore= new Keystore();
$keystoreData = $keystore->generateKeyStore($privateKey, "ABC123456@");
var_dump($keystoreData);

$keystore->RestoreKeyStore($keystoreData, "ABC123456@", "tbnb");
$privateKey = $keystore->getPrivateKey(); 
$publicKey = $keystore->createPublicKey($privateKey);
$address = $keystore->publicKeyToAddress($publicKey, 'tbnb');

var_dump($privateKey->getHex());

?>