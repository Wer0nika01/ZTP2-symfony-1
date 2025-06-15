<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCheckerTest extends TestCase
{
    private MockObject|TranslatorInterface $translator;
    private UserChecker $userChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->userChecker = new UserChecker($this->translator);
    }

    /**
     * Test checkPreAuth method when user is not an instance of App\Entity\User.
     * It should do nothing.
     */
    public function testCheckPreAuthNotUserInstance(): void
    {
        $user = $this->createMock(UserInterface::class);

        // Ensure translator is not called
        $this->translator->expects($this->never())
            ->method('trans');

        // Removed: expectNotToPerformAssertions() as it conflicts with expects($this->never())
        $this->userChecker->checkPreAuth($user);
    }

    /**
     * Test checkPreAuth method when user is an instance of App\Entity\User and is NOT blocked.
     * It should do nothing.
     */
    public function testCheckPreAuthUserNotBlocked(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getIsBlocked')->willReturn(false);

        // Ensure translator is not called
        $this->translator->expects($this->never())
            ->method('trans');

        // Removed: expectNotToPerformAssertions() as it conflicts with expects($this->never())
        $this->userChecker->checkPreAuth($user);
    }

    /**
     * Test checkPreAuth method when user is an instance of App\Entity\User and IS blocked.
     * It should throw a CustomUserMessageAccountStatusException with the correct message.
     */
    public function testCheckPreAuthUserBlocked(): void
    {
        $blockedMessage = 'Your account has been blocked.';
        $user = $this->createMock(User::class);
        $user->method('getIsBlocked')->willReturn(true);

        // Expect the translator to be called with the specific key
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('security.account_blocked_message')
            ->willReturn($blockedMessage);

        // Expect a CustomUserMessageAccountStatusException to be thrown
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage($blockedMessage);

        $this->userChecker->checkPreAuth($user);
    }

    /**
     * Test checkPostAuth method. It should do nothing.
     */
    public function testCheckPostAuth(): void
    {
        $user = $this->createMock(UserInterface::class);

        // Ensure no interactions with the translator
        $this->translator->expects($this->never())
            ->method('trans');

        // Removed: expectNotToPerformAssertions() as it conflicts with expects($this->never())
        $this->userChecker->checkPostAuth($user);
    }
}
