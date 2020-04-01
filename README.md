# binance-php-sdk

This repository contains the PHP-SDK to interact with [Binance DEX](http://binance.org). It's supported by mostly pure PHP code. The documentation for Binance DEX supported methods is [here](http://docs.binance.org).

## Installation

At the moment, you will have to clone the repo and include it in your project. In the future, it will be available through composer.

The SDK relies on [secp256k1-php](https://github.com/Bit-Wasp/secp256k1-php). There is currently no easy method to install secp256k1-php extension, you will need compile it yourself.

### To Install:

libsecp256k1:

    git clone git@github.com:bitcoin-core/secp256k1 && \
    cd secp256k1 &&                                    \
    ./autogen.sh &&                                    \
    ./configure --enable-experimental --enable-module-{ecdh,recovery} && \
     make &&                                           \
     sudo make install &&                              \
     cd ../


secp256k1-php:

    git clone git@github.com:Bit-Wasp/secp256k1-php && \
    cd secp256k1-php/secp256k1 &&                      \
    phpize &&                                          \ 
    ./configure --with-secp256k1 &&                    \  
    make && sudo make install &&                       \
    cd ../../

 
The repository was made on Mac OSX using PHP version `7.15.3`. You will also need a version above 7. It is advised to go with native PHP installation instead of going with XAMPP or WAMPP so to take advantage of secp256k1-php natively. Also, you may need `gmp` extension.

To handle big numbers, the SDK makes use of [brick/math](https://github.com/brick/math) precision library. Please consider using this or another `bcmath` or `gmp` based solutions while dealing with blockchain based numbers.

## Examples
Few examples to interact with SDK are below:

### Keystore reload and getting private key

```php
$keystoreData = '{paste keystore data here or read from a file}';
$keystore= new Keystore();
$keystore->RestoreKeyStore($keystoreData, "{keystore-password}", "tbnb");
$privateKey = $keystore->getPrivateKey();
```

Similary, if you want to see the hex of private key, simply use this method:

`$keystore->getPrivateKey()->getHex();`

### A typical transfer request will look something like this:

```php
$bncClient = new BncClient('https://data-seed-pre-2-s1.binance.org');
$bncClient->initChain();
$bncClient->chooseNetwork("testnet"); // or this can be "mainnet"
$bncClient->setPrivateKey($privateKey);
$response = $bncClient->transfer("tbnb1yqyppmev2m4z96r4svwtjq8eqp653pt6elq33r", "tbnb1hgm0p7khfk85zpz5v0j8wnej3a90w709zzlffd", 0.001, "BNB", "3423423");
```

### Placing an order
```php
$response = $bncClient->NewOrder("BNB_USDT.B-B7C", 1, 0.001, 1, 0, 1); //Symbol, side, price, quantity, sequence, timeinfore
```


## Supported Methods

* Keypair generation
* Keystore reload
* Fetching JSON-RPC functions
* Fetch API functions
* Placing Transactions: Transfer, NewOrder
