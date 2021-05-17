<?php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;

class UserRoutesTest extends ApiTestCase
{
    private $token;
    private $clientWithCredentials;
    private $email = 'test@user.com';
    private $password = 'testUser';
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

    protected function createClientWithCredentials($token = null): Client
    {
        $token = $token ?: $this->getToken();
        $this->clientWithCredentials = static::createClient([], ['headers' => ['authorization' => $token]]);
        return  $this->clientWithCredentials;
    }

    public function testUserRegistrationFailureNoBody(): void
    {
        $response = static::createClient()->request('POST', '/api/register');
        $this->assertResponseStatusCodeSame('400');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testUserRegistrationFailureMissingData(): void
    {
        $originalBody = [
            'login'=> 'testUser',
            'email'=> $this->email,
            'password'=> $this->password,
            'firstname'=> 'Test',
            'lastname'=> 'User',
        ]; 
        $body = $originalBody;
        foreach($body as $key => $entry){
            unset($body[$key]);
            $response = static::createClient()->request('POST', '/api/register', ['json' => $body]);
            $this->assertResponseStatusCodeSame('400');
            $this->assertMatchesJsonSchema($this->errorJsonSchema);
            $body = $originalBody;
        }
    }

    public function testUserRegistrationSuccess(): void
    {
        $body = [
            'login'=> 'testUser',
            'email'=> $this->email,
            'password'=> $this->password,
            'firstname'=> 'Test',
            'lastname'=> 'User',
        ];
        $response = static::createClient()->request('POST', '/api/register', ['json' => $body]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['data' => 'testUser has been registered']);
        $this->assertResponseStatusCodeSame('201');
        $this->createClientWithCredentials();
    }

    public function testUserRegistrationFailureAlreadyRegistered(): void
    {
        $body = [
            'login'=> 'testUser',
            'email'=> $this->email,
            'password'=> $this->password,
            'firstname'=> 'Test',
            'lastname'=> 'User',
        ];
        $response = static::createClient()->request('POST', '/api/register', ['json' => $body]);
        $this->assertResponseStatusCodeSame('400');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testLoginSuccess(): void
    {
        $body = [
            'email'=> $this->email,
            'password'=> $this->password, 
        ];

        $response = static::createClient()->request('POST', '/api/login', ['json' => $body]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema('{
            "$schema": "https://json-schema.org/draft/2020-12/schema",
            "$id": "https://example.com/product.schema.json",
            "title": "Login",
            "description": "Login response containing a token",
            "type": "object",
            "properties": {
              "token": {
                "description": "JWE token containing the user id.",
                "type": "string"
              }
            },
            "required": [ "token" ]
          }');
        $this->assertResponseStatusCodeSame('200');
    }

    public function testLoginFailureNoBody(): void
    {
        $response = static::createClient()->request('POST', '/api/login');
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testLoginFailureMissingData(): void
    {
        $originalBody = [
            'email'=> $this->email,
            'password'=> $this->password, 
        ];
        $body = $originalBody;
        foreach($body as $key => $entry){
            unset($body[$key]);
            $response = static::createClient()->request('POST', '/api/login', ['json' => $body]);
            $this->assertResponseStatusCodeSame('401');
            $this->assertMatchesJsonSchema($this->errorJsonSchema);
            $body = $originalBody;
        }
    }

    public function testLoginFailureWrongData(): void
    {
        $originalBody = [
            'email'=> $this->email,
            'password'=> $this->password, 
        ];
        $body = $originalBody;
        foreach($body as $key => $entry){
            $body[$key] = "Bad data";
            $response = static::createClient()->request('POST', '/api/login', ['json' => $body]);
            $this->assertResponseStatusCodeSame('401');
            $this->assertMatchesJsonSchema($this->errorJsonSchema);
            $body = $originalBody;
        }
    }


    public function testUserUpdateSuccess(): void
    {
        $this->token = $this->getToken();

        $body = [
            'login'=> 'newLogin',
            'email'=> $this->email,
            'firstname'=> 'newFirstname',
            'lastname'=> 'newLastname',
            'passwordVerification'=> $this->password,
        ];

        $response = $this->clientWithCredentials->request('PUT', '/api/user', ['json' => $body]);

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSchema('{
            "$schema": "https://json-schema.org/draft/2020-12/schema",
            "$id": "https://example.com/product.schema.json",
            "title": "User update",
            "description": "User update success containing a sentance and the user object",
            "type": "object",
            "properties": {
              "data": {
                "description": "success message",
                "type": "string"
              },
              "user": {
                "description": "updated user object",
                  "type": "object"
              }
            },
            "required": [ "data", "user" ]
          }');
        $this->assertResponseStatusCodeSame('200');
    }

    public function testUserUpdateFailureNoBody(): void
    {
        $this->token = $this->getToken();
        $response = $this->clientWithCredentials->request('PUT', '/api/user');
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testUserUpdateFailureNoToken(): void
    {
        $body = [
            'login'=> 'newLogin',
            'email'=> $this->email,
            'firstname'=> 'newFirstname',
            'lastname'=> 'newLastname',
            'passwordVerification'=> $this->password,
        ];

        $response = static::createClient()->request('PUT', '/api/user', ['json' => $body]);
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testUserUpdateFailureBadPassword(): void
    {
        $this->token = $this->getToken();
        $body = [
            'login'=> 'newLogin',
            'email'=> $this->email,
            'firstname'=> 'newFirstname',
            'lastname'=> 'newLastname',
            'passwordVerification'=> "bad password",
        ];

        $response = $this->clientWithCredentials->request('PUT', '/api/user', ['json' => $body]);
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testUserUpdateFailureNoPassword(): void
    {
        $this->token = $this->getToken();
        $body = [
            'login'=> 'newLogin',
            'email'=> $this->email,
            'firstname'=> 'newFirstname',
            'lastname'=> 'newLastname',
        ];

        $response = $this->clientWithCredentials->request('PUT', '/api/user', ['json' => $body]);
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
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

    public function testDeleteUserFailureNoToken(): void
    {
        $body = [
            'passwordVerification'=> $this->password,
        ];
        $response = static::createClient()->request('DELETE', '/api/user', ['json' => $body]);

        $this->assertResponseStatusCodeSame('401');
    }

    public function testUserDeleteUserFailureNoBody(): void
    {
        $this->token = $this->getToken();

        $response = $this->clientWithCredentials->request('DELETE', '/api/user');
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testUserDeleteUserFailureBadPassword(): void
    {
        $this->token = $this->getToken();
        $body = [
            'passwordVerification'=> "Bad password",
        ];
        $response = $this->clientWithCredentials->request('DELETE', '/api/user', ['json' => $body]);
        $this->assertResponseStatusCodeSame('401');
        $this->assertMatchesJsonSchema($this->errorJsonSchema);
    }

    public function testDeleteUserSuccess(): void
    {
        $this->token = $this->getToken();
        $body = [
            'passwordVerification'=> $this->password,
        ];
        $response = $this->clientWithCredentials->request('DELETE', '/api/user', ['json' => $body]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame('204');
    }
}
