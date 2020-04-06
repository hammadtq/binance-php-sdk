<?php

namespace Binance\Examples;

require '../../vendor/autoload.php';

use Binance\Types\Byte;
use Binance\Crypto\Keystore;
use Binance\Crypto\Address;
use Binance\Crypto\BIP39\BIP39;
use Binance\Crypto\BIP32;

$password = "";
$entropy = BIP39::generateEntropy(256);
//$mnemonic = BIP39::entropyToMnemonic($entropy);
$mnemonic = 'clarify fossil armed prize quit gossip famous cute usual fee coach rebuild enlist fine zero glance live embark world undo piano little magic degree';
$seed = BIP39::mnemonicToSeedHex($mnemonic, $password);
//$seed = BIP39::mnemonicToEntropy($mnemonic);

unset($entropy); // ignore, forget about this, don't use it!

var_dump($mnemonic); // this is what you print on a piece of paper, etc
var_dump($password); // this is secret of course
var_dump($seed); // this is what you use to generate a key

echo "-----";

$master = BIP32::master_key($seed);
$def = "44'/714'/0'/0/";
$key = BIP32::build_key($master, $def);
var_dump($key[0]);
$keystore = new Keystore();
$privateKey = Byte::init($keystore->createPrivateKeyWithSeed($key[0]));
$publicKey = $keystore->createPublicKey($privateKey);
$address = $keystore->publicKeyToAddress($publicKey, 'tbnb');
var_dump($privateKey);
var_dump($privateKey->getHex());
var_dump(Byte::init(hex2bin($privateKey->getHex())));
var_dump($publicKey);
var_dump($address);

?>