<?php
namespace App\Tools;

use Jose\Component\Core\JWK;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256CBCHS512;
use Jose\Component\Encryption\Compression\CompressionMethodManager;
use Jose\Component\Encryption\Compression\Deflate;
use Jose\Component\Encryption\JWEBuilder;
use Symfony\Component\Dotenv\Dotenv;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\JWESerializerManager;
use Jose\Component\Encryption\Serializer\CompactSerializer;
use Jose\Component\Encryption\JWELoader;
use Symfony\Component\HttpFoundation\JsonResponse;

// The compression method manager with the DEF (Deflate) method.
Class JWT {
    private $jweBuilder;
    private $jweDecrypter;
    private $jwk;

    public function __construct(){
        
        // The algorithm manager with the HS256 algorithm.
        $keyEncryptionAlgorithmManager = new AlgorithmManager([
            new A256KW(),
        ]);

        // The content encryption algorithm manager with the A256CBC-HS256 algorithm.
        $contentEncryptionAlgorithmManager = new AlgorithmManager([
            new A256CBCHS512(),
        ]);
        
        $compressionMethodManager = new CompressionMethodManager([
            new Deflate(),
        ]);
        
        // We instantiate our JWE Builder.
        $this->jweBuilder = new JWEBuilder(
            $keyEncryptionAlgorithmManager,
            $contentEncryptionAlgorithmManager,
            $compressionMethodManager
        );
        // We instantiate our JWE Decrypter.
        $this->jweDecrypter = new JWEDecrypter(
            $keyEncryptionAlgorithmManager,
            $contentEncryptionAlgorithmManager,
            $compressionMethodManager
        );

        // Our key.
        $this->jwk = new JWK([
            'kty' => 'oct',
            'k' => $_ENV["JWT_KEY"],
        ]);     
    }

    private function verifyToken ($token) {
        // The serializer manager. We only use the JWE Compact Serialization Mode.
        $serializerManager = new JWESerializerManager([
            new CompactSerializer(),
        ]);
                
        // We try to load the token.
        $jwe = $serializerManager->unserialize($token);
        
        // We decrypt the token. This method does NOT check the header.
        $isVerified = $this->jweDecrypter->decryptUsingKey($jwe, $this->jwk, 0);
            
        return $isVerified;
    }
        
    public function getToken ($userId) {
            
        // The payload we want to sign. The payload MUST be a string hence we use our JSON Converter.
        $payload = json_encode([
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'iss' => 'My service',
            'aud' => 'Your application',
            'userId' => $userId,
        ]);
        
        $jwe = $this->jweBuilder
            ->create()              // We want to create a new JWE
            ->withPayload($payload) // We set the payload
            ->withSharedProtectedHeader([
                'alg' => 'A256KW',        // Key Encryption Algorithm
                'enc' => 'A256CBC-HS512', // Content Encryption Algorithm
                'zip' => 'DEF',            // We enable the compression (irrelevant as the payload is small, just for the example).
            ])
            ->addRecipient($this->jwk)    // We add a recipient (a shared key or public key).
            ->build();              // We build it

        $serializer = new CompactSerializer(); // The serializer
        $token = $serializer->serialize($jwe, 0); // We serialize the signature at index 0 (we only have one signature).
        return $token;
    }
        
    public function getUserIdFromToken($token){
        // The serializer manager. We only use the JWE Compact Serialization Mode.
        $serializerManager = new JWESerializerManager([
            new CompactSerializer(),
        ]);
                
        // We try to load the token.
        $jwe = $serializerManager->unserialize($token);
        
        // We decrypt the token. This method does NOT check the header.
        $isVerified = $this->jweDecrypter->decryptUsingKey($jwe, $this->jwk, 0);
        $payload = $jwe->getPayload();
        $userId = json_decode($payload)->userId;
        return $userId;
    }

    public function authorization($headers){
        if(!isset($headers['authorization'])){
            return new JsonResponse(['error' => "authorization header missing"], 401);
        }
        $token = $headers['authorization'][0];
        if(!$this->verifyToken($token)){
            return new JsonResponse(['error' => "wrong token"], 401);
        } else {
            return "OK";
        }
    }
}