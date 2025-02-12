<?php

namespace App\Tests\Entities;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserRegistrationTest extends TestCase {
    public function testAllAttributesFirst(): string {
        $this->assertClassHasAttribute('id', User::class);
        $this->assertClassHasAttribute('email', User::class);
        $this->assertClassHasAttribute('roles', User::class);
        $this->assertClassHasAttribute('password', User::class);
        $this->assertClassHasAttribute('isVerified', User::class);
        $this->assertClassHasAttribute('username', User::class);
        return 'All attributes are present.';
    }

    public function testSetAttributesSecond(): string {
        $this->assertEquals('test@test.de', (new User())->setEmail('test@test.de')->getEmail());
        $this->assertEquals('test', (new User())->setPassword('test')->getPassword());
        $this->assertEquals('Testusername', (new User())->setUsername('Testusername')->getUsername());
        return 'Setters are working.';
    }

    public function testGetAttributesThird(): string {
        $user = new User();
        $user->setEmail('test@test.de');
        $user->setPassword('test');
        $user->setUsername('Testusername');
        $this->assertEquals('test@test.de', $user->getEmail());
        $this->assertEquals('test', $user->getPassword());
        $this->assertEquals('Testusername', $user->getUsername());
        return 'Getters are working.';
    }
}