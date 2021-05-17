<?php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;

class CartRoutesTest extends ApiTestCase
{
    private $token;
    private $clientWithCredentials;
    private $email = 'test@test.com';
    private $password = 'testtest';
    private $errorJsonSchema = '{
        "$schema": "https://json-schema.org/draft/2020-12/schema",
        "$id": "https://example.com/product.schema.json",
        "title": "Registration failure",
        "description": "Error describing why the registration response failed",
        "type": "object",
        "properties": {
          "error": {
            "description": "registration error",
            "type": "string"
          }
        },
        "required": [ "error" ]
      }';

    public function setUp(): void
    {
        self::bootKernel();
    }

    public function getToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        $body = [
            'email'=> $this->email,
            'password'=> $this->password,
        ];
        $response = static::createClient()->request('POST', '/api/login', ['json' => $body]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent());
        $this->token = $data->token;
        $this->createClientWithCredentials();
        return $this->token;
    }

    protected function createClientWithCredentials($token = null): Client
    {
        $token = $token ?: $this->getToken();
        $this->clientWithCredentials = static::createClient([], ['headers' => ['authorization' => $token]]);
        return  $this->clientWithCredentials;
    }

    public function getTestProductId(): int
    {
        $this->token = $this->getToken();
 
        $response = $this->clientWithCredentials->request('GET', '/api/products');
        $data = json_decode($response->getContent());
        $data = $data->data;
        for($i = 0; $i<sizeof($data); $i++ ){
            if ($data[$i]->name == 'Test product'){
                return $data[$i]->id;
            }
        }
        print_r("testCart");
    }

    public function testAddToCartSuccess(): void
    {
        $this->token = $this->getToken();
        $product_id = 2;
        $response = $this->clientWithCredentials->request('PUT', '/api/cart/'.$product_id);
        $this->assertResponseStatusCodeSame('301');
    }

    public function testAddToCartFailureProductNotFound(): void
    {
        $this->token = $this->getToken();
        $product_id = 3;
        $response = $this->clientWithCredentials->request('PUT', '/api/cart/'.$product_id);
        $this->assertResponseStatusCodeSame('404');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testDeleteFromCartSuccess(): void
    {
        $this->token = $this->getToken();
        $product_id = 2;
        $response = $this->clientWithCredentials->request('DELETE', '/api/cart/'.$product_id);
        $this->assertResponseStatusCodeSame('200');
    }

    public function testDeleteFromCartFailureProductNotFound(): void
    {
        $this->token = $this->getToken();
        $product_id = 1;
        $response = $this->clientWithCredentials->request('DELETE', '/api/cart/'.$product_id);
        $this->assertResponseStatusCodeSame('404');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testGetCartSuccess(): void
    {
        $this->token = $this->getToken();
        $response = $this->clientWithCredentials->request('GET', '/api/cart');
        $this->assertResponseStatusCodeSame('200');
    }

    public function testDeleteCartSuccess(): void
    {
        $this->token = $this->getToken();
        $product_id = 1;
        $response = $this->clientWithCredentials->request('DELETE', '/api/cart');
        $this->assertResponseStatusCodeSame('204');
    }

    public function testDeleteFromCartFailureCartEmpty(): void
    {
        $this->token = $this->getToken();
        $product_id = 2;
        $response = $this->clientWithCredentials->request('DELETE', '/api/cart/'.$product_id);
        $this->assertResponseStatusCodeSame('404');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testValidateCartFailureCartEmpty(): void
    {
        $this->token = $this->getToken();
        $response = $this->clientWithCredentials->request('POST', '/api/cart/validate');
        $this->assertResponseStatusCodeSame('404');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function seedCart(): void
    {
        $this->token = $this->getToken();
        $product_id = 2;
        for($i = 0; $i < 3; $i++){
            $response = $this->clientWithCredentials->request('PUT', '/api/cart/'.$product_id);
        }
    }

    public function testValidateCartSuccess(): void
    {
        $this->seedCart();
        $this->token = $this->getToken();
        $response = $this->clientWithCredentials->request('POST', '/api/cart/validate');
        $this->assertResponseStatusCodeSame('201');
    }
}
