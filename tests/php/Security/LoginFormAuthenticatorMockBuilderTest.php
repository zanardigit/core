<?php

declare(strict_types=1);

namespace Bolt\Tests\Security;

use Bolt\Entity\User;
use Bolt\Repository\UserRepository;
use Bolt\Security\LoginFormAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class LoginFormAuthenticatorMockBuilderTest extends \PHPUnit\Framework\TestCase
{
    function test_get_login_url()
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('bolt_login')
            ->willReturn('test_route');

        $res = $this->getTestObj(null, $router, null, null)->start($this->createMock(Request::class));
        $this->assertEquals('test_route', $res->getTargetUrl());
    }

    function test_get_user()
    {
        $userRepository = $this->createConfiguredMock(UserRepository::class, [
            'findOneBy' => $this->createMock(User::class)
        ]);
        $csrfTokenManager = $this->createConfiguredMock(CsrfTokenManagerInterface::class, [
            'isTokenValid' => true
        ]);

        $token = ['csrf_token' => null, 'username' => null];

        $res = $this->getTestObj($userRepository, null, $csrfTokenManager, null)->getUser($token, $this->createMock(UserProviderInterface::class));
        $this->assertInstanceOf(User::class, $res);
    }

    function test_get_user_throws()
    {
        $csrfTokenManager = $this->createConfiguredMock(CsrfTokenManagerInterface::class, [
            'isTokenValid' => false
        ]);

        $this->expectException(InvalidCsrfTokenException::class);
        $this->getTestObj(null, null, $csrfTokenManager, null)->getUser(['csrf_token' => null], $this->createMock(UserProviderInterface::class));
    }
    
    private function getTestObj(?UserRepository $userRepository, ?RouterInterface $router, ?CsrfTokenManagerInterface $csrfTokenManager, ?UserPasswordEncoderInterface $userPasswordEncoder): LoginFormAuthenticator
    {
        return new LoginFormAuthenticator(
            $userRepository ?? $this->createMock(UserRepository::class),
            $router ?? $this->createMock(RouterInterface::class), 
            $csrfTokenManager ?? $this->createMock(CsrfTokenManagerInterface::class),
            $userPasswordEncoder ?? $this->createMock(UserPasswordEncoderInterface::class)
        );
    }
}