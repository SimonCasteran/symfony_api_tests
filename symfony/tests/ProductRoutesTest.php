<?php

namespace App\Tests;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;

class ProductRoutesTest extends ApiTestCase
{
    private $token;
    private $clientWithCredentials;
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
            'email'=> "test@test.com",
            'password'=> "testtest",
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

    public function testProductRegistrationFailureMissingData(): void
    {
        $this->token = $this->getToken();
        $originalBody = [
            'name'=> 'Test product',
            'description'=> 'Not to be mistaken with a product.',
            'photo'=> 'https://product.com/product.jpg',
            'price'=> '23',
        ]; 
        $body = $originalBody;
        foreach($body as $key => $entry){
            unset($body[$key]);
            $response = $this->clientWithCredentials->request('POST', '/api/product', ['json' => $body]);
            $this->assertResponseStatusCodeSame('400');
            $this->assertMatchesJsonSchema($this->errorJsonSchema);
            $body = $originalBody;
        }
    }

    public function testProductRegistrationFailureNoToken(): void
    {
        $body = [
            'name'=> 'Test product',
            'description'=> 'Not to be mistaken with a product.',
            'photo'=> 'https://product.com/product.jpg',
            'price'=> '23',
        ]; 
        $response = static::createClient()->request('POST', '/api/product', ['json' => $body]);
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testProductRegistrationFailureNoBody(): void
    {
        $this->token = $this->getToken();
        $response = $this->clientWithCredentials->request('POST', '/api/product');
        $this->assertResponseStatusCodeSame('400');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testProductRegistrationSuccess(): void
    {
        $this->token = $this->getToken();
        $body = [
            'name'=> 'Test product',
            'description'=> 'Not to be mistaken with a product.',
            'photo'=> 'https://product.com/product.jpg',
            'price'=> '23',
        ]; 
        $response = $this->clientWithCredentials->request('POST', '/api/product', ['json' => $body]);
        $this->assertResponseStatusCodeSame('201');
        $this->assertMatchesJsonSchema('{
            "$schema": "https://json-schema.org/draft/2020-12/schema",
            "$id": "https://example.com/product.schema.json",
            "title": "Product creation success response",
            "description": "Product creation success containing a sentance and the product object",
            "type": "object",
            "properties": {
              "data": {
                "description": "success message",
                "type": "string"
              },
              "product": {
                "description": "Product created",
                  "type": "object"
              }
            },
            "required": [ "data", "product" ]
          }');
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
    }

    public function testGetAllProductsSuccess(): void
    {
        $this->token = $this->getToken();
 
        $response = $this->clientWithCredentials->request('GET', '/api/products');
        $this->assertResponseStatusCodeSame('200');
        $this->assertMatchesJsonSchema('{
            "$schema": "https://json-schema.org/draft/2020-12/schema",
            "$id": "https://example.com/product.schema.json",
            "title": "get all products",
            "description": "Get an array of all products",
            "type": "object",
            "properties": {
              "data": {
                "description": "array containing all products",
                "type": "array"
              }
            },
            "required": [ "data" ]
          }');
    }

    public function testGetAllProductsFailureNoToken(): void
    {
        $this->token = $this->getToken();
 
        $response = static::createClient()->request('GET', '/api/products');
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testGetProductByIdSuccess(): void
    {
        $this->token = $this->getToken();
        $id = $this->getTestProductId();
        $body = [
            'name'=> 'Test product',
            'description'=> 'Not to be mistaken with a product.',
            'photo'=> 'https://product.com/product.jpg',
            'price'=> '23',
        ]; 
        $response = $this->clientWithCredentials->request('PUT', '/api/product/'.$id, ['json' => $body]);
        $this->assertResponseStatusCodeSame('200');
        $this->assertMatchesJsonSchema('{
            "$schema": "https://json-schema.org/draft/2020-12/schema",
            "$id": "https://example.com/product.schema.json",
            "title": "Product update success response",
            "description": "Product update success containing a sentance and the product object",
            "type": "object",
            "properties": {
              "data": {
                "description": "success message",
                "type": "string"
              },
              "product": {
                "description": "Product created",
                  "type": "object"
              }
            },
            "required": [ "data", "product" ]
          }');
    }

    public function testGetProductByIdFailureNoToken(): void
    {
        $id = $this->getTestProductId();
        $response = static::createClient()->request('GET', '/api/product/'.$id);
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testUpdateProductByIdSuccess(): void
    {
        $id = $this->getTestProductId();
        $response = static::createClient()->request('PUT', '/api/product/'.$id);
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testUpdateProductByIdFailureNoToken(): void
    {
        $id = $this->getTestProductId();
        $response = static::createClient()->request('PUT', '/api/product/'.$id);
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testUpdateProductByIdFailureBadId(): void
    {
        $id = $this->getTestProductId();
        $response = static::createClient()->request('PUT', '/api/product/0');
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testProductDeleteSuccess(): void
    {
        $this->token = $this->getToken();
        $id = $this->getTestProductId();
        $response = $this->clientWithCredentials->request('DELETE', '/api/product/'.$id);
        $this->assertResponseStatusCodeSame('204');
    }
}
