<?php

namespace Binance\Crypto;

use Binance\Types\Byte;
use Binance\Crypto\Bech32;
use Binance\Utils\UtilFunctions;
use Ramsey\Uuid\Uuid;
use Binance\Crypto\Keccak;

class Keystore
{
    /**
     * @var Byte
     */
    private $privateKey;

    /**
     * @var Byte
     */
    private $publicKey;

    /**
     * @var Address
     */
    private $address;

    /**
     * @var TransactionSigner
     */
    private $transactionSigner;


    static private $context;


    public static function getContext()
    {
        if (self::$context == null) {
            self::$context = \secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
        }

        return self::$context;
    }

    /**
     * @param string $data
     * @param string $passphrase
     * @throws Exception
     */
    public function RestoreKeyStore(string $data, string $passphrase, string $addrPrefix)
    {
        try {
            $data = json_decode($data)->crypto;
        } catch (Exception $e) {
            throw new InvalidArgumentException('Argument is not a valid JSON string.');
        }
        
        switch ($data->kdf) {
            case 'pbkdf2':
                $derivedKey = $this->derivePbkdf2EncryptedKey(
                    $passphrase,
                    $data->kdfparams->prf,
                    $data->kdfparams->salt,
                    $data->kdfparams->c,
                    $data->kdfparams->dklen
                );
                break;
            case 'scrypt':
                $derivedKey = $this->deriveScryptEncryptedKey(
                    $passphrase,
                    $data->kdfparams->salt,
                    $data->kdfparams->n,
                    $data->kdfparams->r,
                    $data->kdfparams->p,
                    $data->kdfparams->dklen
                );
                break;
            default:
                throw new Exception(sprintf('Unsupported KDF function "%s".', $data->kdf));
        }
        // if (! $this->validateDerivedKey($derivedKey, $data->ciphertext, $data->mac)) {
        //     throw new Exception('Passphrase is invalid.');
        // }

        $this->privateKey = $this->decryptPrivateKey($data->ciphertext, $derivedKey, $data->cipher, $data->cipherparams->iv);
        $this->publicKey = $this->createPublicKey($this->privateKey);
        $this->address = $this->parseAddress($this->publicKey, $addrPrefix);
    }

    /**
     * @param string $passphrase
     * @param string $prf
     * @param string $salt
     * @param int $c
     * @param $dklen
     * @return string
     * @throws Exception
     */
    private function derivePbkdf2EncryptedKey(string $passphrase, string $prf, string $salt, int $c, $dklen)
    {
        if ($prf != 'hmac-sha256') {
            throw new Exception(sprintf('Unsupported PRF function "%s".', $prf));
        }
        return hash_pbkdf2('sha256', $passphrase, pack('H*', $salt), $c,  $dklen * 2);
    }

    /**
     * @param string $passphrase
     * @param string $salt
     * @param int $n
     * @param int $r
     * @param int $p
     * @param int $dklen
     * @return string
     */
    private function deriveScryptEncryptedKey(string $passphrase, string $salt, int $n, int $r, int $p, int $dklen)
    {
        return scrypt($passphrase, pack('H*', $salt), $n, $r, $p, $dklen);
    }

    /**
     * @param string $ciphertext
     * @param string $key
     * @param string $cipher
     * @param string $iv
     * @return Byte
     * @throws Exception
     */
    private function decryptPrivateKey(string $ciphertext, string $key, string $cipher, string $iv): Byte
    {
        $output = openssl_decrypt(pack('H*', $ciphertext), $cipher, pack('H*', $key),OPENSSL_RAW_DATA, pack('H*', $iv));
        return Byte::init($output);
    }

    /**
     * @param Byte $privateKey
     * @return Byte
     * @throws Exception
     */
    public function createPublicKey(Byte $privateKey): Byte
    {
        $context = secp256k1_context_create(SECP256K1_CONTEXT_SIGN | SECP256K1_CONTEXT_VERIFY);
        /** @var resource $publicKey */
        $publicKey = null;
        $result = secp256k1_ec_pubkey_create($context, $publicKey, $privateKey->getBinary());
        if ($result === 1){
            $serialized = '';
            $flags = SECP256K1_EC_COMPRESSED;
            if (1 !== secp256k1_ec_pubkey_serialize($context, $serialized, $publicKey, $flags)) {
                    throw new Exception('secp256k1_ec_pubkey_serialize: failed to serialize public key');
                }
                //$serialized = substr($serialized, 1, 64);
                unset($publicKey, $context);
                return Byte::init($serialized);
        }
        throw new Exception('secp256k1_pubkey_create: secret key was invalid');
    }

    /**
     * @param Byte $publicKey
     * @return Address
     * @throws Exception
     */
    private function parseAddress(Byte $publicKey, String $addrPrefix, Bool $compressedKey = true): String
    {   
        if($compressedKey){
            $compressed = $publicKey->getHex();
        }else{
            $compressed = $publicKey;
        }
        
        $sha256 = hash('sha256', hex2bin($compressed));
        $ripemd60 = hash('ripemd160', hex2bin($sha256));

        $chars = array_values(unpack('C*', hex2bin($ripemd60)));

        $bech32 = new Bech32();

        $convertedBits = $bech32->convertBits($chars, count($chars), 8, 5, true);   
        $bech32EncodedAddress = $bech32->encode($addrPrefix, $convertedBits);
        
        return $bech32EncodedAddress;
    }

    /**
     * @return Byte
     */
    public function getPrivateKey(): Byte
    {
        return $this->privateKey;
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param Transaction $transaction
     * @param Uint $chainId
     * @return Byte
     * @throws Exception
     */
    public function signTransaction(Transaction $transaction, Uint $chainId): Byte
    {
        if (empty($this->transactionSigner)) {
            $this->transactionSigner = new TransactionSigner($chainId);
        }
        return $this->transactionSigner->sign($transaction, $this->getPrivateKey());
    }

    /**
     * Generates a keystore object (web3 secret storage format) given a private key to store and a password.
     * @param {string} privateKeyHex the private key hexstring.
     * @param {string} password the password.
     * @return {object} the keystore object.
     */
    function generateKeyStore ($privateKeyHex, $password) {
        $salt = \openssl_random_pseudo_bytes(32);
        $iv = \openssl_random_pseudo_bytes(16);

        $cipherAlg = "aes-256-ctr";
    
        $kdf = "pbkdf2";

        $kdfparams = (object)(array('dklen' => 32,
                                    'salt' => bin2hex($salt),
                                    'c' => 262144,
                                    'prf' => "hmac-sha256"));

            
        $derivedKey = hash_pbkdf2('sha256', $password, $salt, $kdfparams->c,  $kdfparams->dklen, true);
        
        $derivedKeySlice = substr($derivedKey, 0, 32);

        $ciphertext = openssl_encrypt(hex2bin($privateKeyHex), $cipherAlg, $derivedKeySlice, OPENSSL_RAW_DATA, $iv);

        $bufferValue = bin2hex(substr($derivedKey, 16, 32)).bin2hex($ciphertext);
        var_dump($bufferValue);

        $uuid4 = Uuid::uuid4();

        $json = json_encode(array('version' => 1,
            'id' => $uuid4->toString(),
            'crypto' => array('ciphertext' => bin2hex($ciphertext),
                        'cipherparams' => array('iv' => bin2hex($iv)),
                        'cipher' => $cipherAlg,
                        'kdf' => $kdf,
                        'kdfparams' => $kdfparams,
                        'mac' => keccak::hash($bufferValue, 256))
        ));


        return $json;
    }


    public function createPrivateKey()
    {
        do {
            $key = \openssl_random_pseudo_bytes(32);
        } while (secp256k1_ec_seckey_verify(self::getContext(), $key) == 0);
        return $key;
    }

    public function createPrivateKeyWithSeed($seed)
    {
        do {
            $key = $seed;
            var_dump(strlen($key));
        } while (secp256k1_ec_seckey_verify(self::getContext(), $key) == 0);
        return $key;
    }

    public function privateKeyToPublicKey($privateKey){
        return($this->createPublicKey($privateKey));
    } 

    public function publicKeyToAddress($publicKey, $addrPrefix){
        return($this->parseAddress($publicKey, $addrPrefix));
    }

    //This function is only used while creating from mnemonic
    public function mnemonicPublicKeyToAddress($publicKey, $addrPrefix){
        return($this->parseAddress($publicKey, $addrPrefix, false));
    } 

}