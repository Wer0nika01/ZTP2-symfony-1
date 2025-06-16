<?php

namespace App\Tests\Unit\Controller;

use App\Controller\DashboardController;
use App\Entity\User;
use App\Repository\EventRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment; // Using Twig\Environment to mock the render method

class DashboardControllerTest extends TestCase
{
    private $eventRepository;
    private $tokenStorage; // Will still be used to set up the mocked user if needed externally
    private $twig;
    private $user;

    protected function setUp(): void
    {
        // Mock the EventRepository
        $this->eventRepository = $this->createMock(EventRepository::class);

        // Mock the User entity
        $this->user = $this->createMock(User::class);

        // Mock the TokenStorageInterface (though it will be directly used to return the user in the controller mock)
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        // Mock Twig Environment to simulate the render method's internal call
        $this->twig = $this->createMock(Environment::class);
    }

    /**
     * Helper method to create a DashboardController instance with mocked dependencies.
     * This directly stubs the protected methods of AbstractController that we need to control.
     */
    private function createController(): DashboardController
    {
        // Create a partial mock of DashboardController.
        // We need to mock 'getUser' and 'render' because they are protected methods of AbstractController
        // that DashboardController inherits and calls internally, and they rely on a container.
        $controller = $this->getMockBuilder(DashboardController::class)
            ->setConstructorArgs([$this->eventRepository])
            ->onlyMethods(['getUser', 'render']) // Only mock these two methods
            ->getMock();

        // Configure the mocked getUser() method to return our mock user.
        $controller->method('getUser')->willReturn($this->user);

        // Configure the mocked render() method to simulate rendering a Twig template.
        // Instead of actually rendering, we'll delegate to our mocked Twig environment.
        $controller->method('render')
            ->willReturnCallback(function (string $view, array $parameters = []) {
                // The mocked Twig environment will be called here
                $content = $this->twig->render($view, $parameters);
                return new Response($content);
            });

        return $controller;
    }

    public function testIndex(): void
    {
        $controller = $this->createController();

        // Define expected data from repositories
        $activeEvents = ['event1', 'event2'];
        $upcomingEvents = ['event3', 'event4'];

        // Configure EventRepository mocks to expect calls and return data
        $this->eventRepository->expects($this->once())
            ->method('findActiveEvents')
            ->with($this->user, 5)
            ->willReturn($activeEvents);

        $this->eventRepository->expects($this->once())
            ->method('findUpcomingEvents')
            ->with($this->user, 5)
            ->willReturn($upcomingEvents);

        // Configure Twig mock to expect the render call with specific template and parameters
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'dashboard/index.html.twig',
                $this->equalTo([
                    'activeEvents' => $activeEvents,
                    'upcomingEvents' => $upcomingEvents,
                ])
            )
            ->willReturn('<html>dashboard content</html>'); // Return some dummy HTML content for the response

        // Execute the index method of the controller
        $response = $controller->index();

        // Assertions for the HTTP Response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('dashboard content', $response->getContent());
    }
}
