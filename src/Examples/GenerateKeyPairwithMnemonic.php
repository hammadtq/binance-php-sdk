<?php

namespace Binance\Examples;

require '../../vendor/autoload.php';

use Binance\Types\Byte;
use Binance\Crypto\Keystore;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;

$network = Bitcoin::getNetwork();

// Generate a mnemonic
$random = new Random();
$entropy = $random->bytes(Bip39Mnemonic::MAX_ENTROPY_BYTE_LEN);

$bip39 = MnemonicFactory::bip39();
$seedGenerator = new Bip39SeedGenerator();
$mnemonic = $bip39->entropyToMnemonic($entropy);
var_dump($mnemonic);

// Derive a seed from mnemonic/password
$seed = $seedGenerator->getSeed($mnemonic, '');
echo $seed->getHex() . "\n";

$hdFactory = new HierarchicalKeyFactory();
$bip32 = $hdFactory->fromEntropy($seed);

$derivedPath = $bip32->derivePath("44'/714'/0'/0/0 ");

$privateKey = $derivedPath->getPrivateKey()->getHex();
var_dump("Private Key: ".$privateKey);

$publicKey = $derivedPath->getPublicKey()->getHex();
var_dump("Public Key: ".$publicKey);

$keystore = new Keystore();
$address = $keystore->mnemonicPublicKeyToAddress(Byte::init($publicKey), 'tbnb');
var_dump("Address: ".$address);

?>