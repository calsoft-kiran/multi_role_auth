<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserApiTest extends WebTestCase
{
    public function testGetUserProfile()
    {
        $client = static::createClient();

        // Authenticate and get JWT Token
        $client->request('POST', 'http://127.0.0.1:8000/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'manager@gmil.com',
            'password' => 'manager111'
        ]));
        $response = $client->getResponse();
        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $token = $data['token'];
        // Use the token to access a secured endpoint
        $client->request('GET', 'http://127.0.0.1:8000/api/common/user/profile', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());

        $profileData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('email', $profileData['data']);
        $this->assertEquals('manager@gmil.com', $profileData['data']['email']);
    }
}
