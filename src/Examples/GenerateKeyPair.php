<?php

namespace Binance\Examples;

require '../../vendor/autoload.php';

use Binance\Crypto\Keystore;
use Binance\Types\Byte;
use Binance\Crypto\Address;


$keystore = new Keystore();
$privateKey = Byte::init($keystore->createPrivateKey());
$publicKey = $keystore->createPublicKey($privateKey);
$address = $keystore->publicKeyToAddress($publicKey, 'tbnb');
var_dump($privateKey);
var_dump($privateKey->getHex());
var_dump(Byte::init(hex2bin($privateKey->getHex())));
var_dump($publicKey);
var_dump($address);