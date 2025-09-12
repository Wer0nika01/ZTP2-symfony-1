<?php

/**
 * Dashboard controller Test.
 */

namespace App\Tests\Unit\Controller;

use App\Controller\DashboardController;
use App\Entity\User;
use App\Repository\EventRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

/**
 * Class Dashboard controller Test.
 */
class DashboardControllerTest extends TestCase
{
    private object $eventRepository;
    private object $twig;
    private object $user;

    /**
     * Test index.
     */
    public function testIndex(): void
    {
        $controller = $this->createController();

        $activeEvents = ['event1', 'event2'];
        $upcomingEvents = ['event3', 'event4'];

        $this->eventRepository->expects($this->once())
            ->method('findActiveEvents')
            ->with($this->user, 5)
            ->willReturn($activeEvents);

        $this->eventRepository->expects($this->once())
            ->method('findUpcomingEvents')
            ->with($this->user, 5)
            ->willReturn($upcomingEvents);

        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'dashboard/index.html.twig',
                $this->equalTo([
                    'activeEvents' => $activeEvents,
                    'upcomingEvents' => $upcomingEvents,
                ])
            )
            ->willReturn(value: '<html lang="">dashboard content</html>');

        $response = $controller->index();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('dashboard content', $response->getContent());
    }

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        $this->eventRepository = $this->createMock(EventRepository::class);

        $this->user = $this->createMock(User::class);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);
        $tokenStorage->method('getToken')->willReturn($token);

        $this->twig = $this->createMock(Environment::class);
    }

    /**
     * Helper method to create a DashboardController instance with mocked dependencies.
     *
     * @return DashboardController Dashboard controller
     */
    private function createController(): DashboardController
    {
        $controller = $this->getMockBuilder(DashboardController::class)
            ->setConstructorArgs([$this->eventRepository])
            ->onlyMethods(['getUser', 'render'])
            ->getMock();

        $controller->method('getUser')->willReturn($this->user);

        $controller->method('render')
            ->willReturnCallback(function (string $view, array $parameters = []) {
                $content = $this->twig->render($view, $parameters);

                return new Response($content);
            });

        return $controller;
    }
}
