<?php

namespace App\Tests\Unit\Controller;

use App\Controller\CategoryController;
use App\Entity\Category;
use App\Form\Type\CategoryType;
use App\Service\CategoryServiceInterface; // Poprawiona ścieżka
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface; // Poprawiona ścieżka
use Symfony\Component\Form\FormView; // Poprawiona ścieżka
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface; // For generateUrl mock
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface; // For pagination mock

class CategoryControllerTest extends TestCase
{
    private MockObject|CategoryServiceInterface $categoryService;
    private MockObject|TranslatorInterface $translator;
    private MockObject|CategoryController $controller;
    private MockObject|FormView $mockFormView;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryService = $this->createMock(CategoryServiceInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->mockFormView = $this->createMock(FormView::class);

        // Create a partial mock for the controller to override its base methods.
        $this->controller = $this->getMockBuilder(CategoryController::class)
            ->setConstructorArgs([$this->categoryService, $this->translator])
            ->setMethods(['createForm', 'render', 'redirectToRoute', 'addFlash', 'generateUrl'])
            ->getMock();

        // FIX: Configure createForm mock in setUp to return a FormInterface mock
        // whose createView() method always returns the same $this->mockFormView.
        // This ensures consistent FormView object identity for render assertions.
        $this->controller->method('createForm')->willReturnCallback(function() {
            $formMock = $this->createMock(FormInterface::class);
            $formMock->method('handleRequest')->willReturnSelf();
            $formMock->method('createView')->willReturn($this->mockFormView);
            // Default behavior: not submitted, not valid.
            $formMock->method('isSubmitted')->willReturn(false);
            $formMock->method('isValid')->willReturn(false);
            return $formMock;
        });


        // Configure render mock: simply return a new Response object
        $this->controller->method('render')->willReturn(new Response());

        // Configure redirectToRoute mock: return a RedirectResponse with a predictable URL.
        $this->controller->method('redirectToRoute')->willReturnCallback(function($route, $params = [], $status = 302) {
            // Simulate generateUrl's behavior for RedirectResponse target URL
            $url = '/mocked/redirect/' . str_replace('_', '/', $route);
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return new RedirectResponse($url, $status);
        });

        // Configure addFlash mock: it's a void method, so just expect it.
        $this->controller->method('addFlash');

        // Configure generateUrl mock: return a predictable URL string
        $this->controller->method('generateUrl')->willReturnCallback(function($route, $params = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH) {
            $url = '/mocked/url/' . str_replace('_', '/', $route);
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            return $url;
        });

        // Configure translator mock: just return the original key
        $this->translator->method('trans')->willReturnArgument(0);
    }

    // --- Index Action Tests ---

    public function testIndex(): void
    {
        $page = 1;
        $pagination = $this->createMock(PaginationInterface::class);

        $this->categoryService->expects($this->once())
            ->method('getPaginatedList')
            ->with($page)
            ->willReturn($pagination);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/index.html.twig', ['pagination' => $pagination])
            ->willReturn(new Response());

        $request = Request::create('/category', 'GET');
        // Call index with only the $page integer, as per method signature and MapQueryParameter
        $response = $this->controller->index($page);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testIndexWithDefaultPage(): void
    {
        $pagination = $this->createMock(PaginationInterface::class);

        $this->categoryService->expects($this->once())
            ->method('getPaginatedList')
            ->with(1) // Default page is 1
            ->willReturn($pagination);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/index.html.twig', ['pagination' => $pagination])
            ->willReturn(new Response());

        $request = Request::create('/category', 'GET');
        // Call index with no arguments to test the default $page = 1
        $response = $this->controller->index();

        $this->assertInstanceOf(Response::class, $response);
    }

    // --- View Action Tests ---

    public function testView(): void
    {
        $category = $this->createMock(Category::class);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/view.html.twig', ['category' => $category])
            ->willReturn(new Response());

        $response = $this->controller->view($category);

        $this->assertInstanceOf(Response::class, $response);
    }

    // --- Create Action Tests ---

    public function testCreateGetRequestRendersForm(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($this->mockFormView);
        // FIX: Configure this specific createForm call to return the pre-configured form mock
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(CategoryType::class, $this->isInstanceOf(Category::class))
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/create.html.twig', ['form' => $this->mockFormView])
            ->willReturn(new Response());

        $request = Request::create('/category/create', 'GET');
        $response = $this->controller->create($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCreatePostRequestValidForm(): void
    {
        $category = new Category(); // Use a real entity to ensure it's passed
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        // FIX: Ensure form is always submitted and valid for this test case
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($this->mockFormView); // Crucial for render if this path were taken
        // FIX: Explicitly configure createForm to return *this specific* form mock for this test
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(CategoryType::class, $this->isInstanceOf(Category::class))
            ->willReturn($form);

        $this->categoryService->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Category::class)); // Check if an instance of Category is passed

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.created_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('category_index');

        $request = Request::create('/category/create', 'POST', ['title' => 'New Category']);
        $response = $this->controller->create($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/mocked/redirect/category/index', $response->getTargetUrl());
    }

    public function testCreatePostRequestInvalidForm(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        // FIX: Explicitly configure createForm to return *this specific* form mock for this test
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(CategoryType::class, $this->isInstanceOf(Category::class))
            ->willReturn($form);

        $this->categoryService->expects($this->never())->method('save');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/create.html.twig', ['form' => $this->mockFormView])
            ->willReturn(new Response());

        $request = Request::create('/category/create', 'POST', ['title' => '']); // Invalid title
        $response = $this->controller->create($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    // --- Edit Action Tests ---

    public function testEditGetRequestRendersForm(): void
    {
        $category = $this->createMock(Category::class);
        $categoryId = 1;
        $category->method('getId')->willReturn($categoryId);

        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->expects($this->once()) // Ensure createForm is called once
        ->method('createForm')
            ->with(CategoryType::class, $category, $this->callback(function($options) use ($categoryId) {
                return $options['method'] === 'PUT' && $options['action'] === '/mocked/url/category/edit?id=' . $categoryId;
            }))
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/edit.html.twig', [
                'form' => $this->mockFormView,
                'category' => $category,
            ])
            ->willReturn(new Response());

        $request = Request::create('/category/1/edit', 'GET');
        $response = $this->controller->edit($request, $category);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEditPutRequestValidForm(): void
    {
        $category = $this->createMock(Category::class);
        $categoryId = 1;
        $category->method('getId')->willReturn($categoryId); // Ensure getId is callable for generateUrl

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        // FIX: Ensure form is always submitted and valid for this test case
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($this->mockFormView); // Crucial for render if this path were taken
        // FIX: Explicitly configure createForm to return *this specific* form mock for this test
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(
                CategoryType::class,
                $category,
                $this->callback(function($options) use ($categoryId) {
                    return $options['method'] === 'PUT' && $options['action'] === '/mocked/url/category/edit?id=' . $categoryId;
                })
            )
            ->willReturn($form);

        $this->categoryService->expects($this->once())
            ->method('save')
            ->with($category);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.edited_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('category_index');

        $request = Request::create('/category/1/edit', 'PUT', ['title' => 'Updated Category']);
        $response = $this->controller->edit($request, $category);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/mocked/redirect/category/index', $response->getTargetUrl());
    }

    public function testEditPutRequestInvalidForm(): void
    {
        $category = $this->createMock(Category::class);
        $categoryId = 1;
        $category->method('getId')->willReturn($categoryId);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        // FIX: Explicitly configure createForm to return *this specific* form mock for this test
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(
                CategoryType::class,
                $category,
                $this->callback(function($options) use ($categoryId) {
                    return $options['method'] === 'PUT' && $options['action'] === '/mocked/url/category/edit?id=' . $categoryId;
                })
            )
            ->willReturn($form);

        $this->categoryService->expects($this->never())->method('save');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/edit.html.twig', [
                'form' => $this->mockFormView,
                'category' => $category,
            ])
            ->willReturn(new Response());

        $request = Request::create('/category/1/edit', 'PUT', ['title' => '']); // Invalid title
        $response = $this->controller->edit($request, $category);

        $this->assertInstanceOf(Response::class, $response);
    }

    // --- Delete Action Tests ---

    public function testDeleteGetRequestCanBeDeleted(): void
    {
        $category = $this->createMock(Category::class);
        $categoryId = 1;
        $category->method('getId')->willReturn($categoryId);

        $this->categoryService->expects($this->once())
            ->method('canBeDeleted')
            ->with($category)
            ->willReturn(true); // Can be deleted

        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->expects($this->once()) // Ensure createForm is called once
        ->method('createForm')
            ->with(FormType::class, $category, $this->callback(function($options) use ($categoryId) {
                return $options['method'] === 'DELETE' && $options['action'] === '/mocked/url/category/delete?id=' . $categoryId;
            }))
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/delete.html.twig', [
                'form' => $this->mockFormView,
                'category' => $category,
            ])
            ->willReturn(new Response());

        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $request = Request::create('/category/1/delete', 'GET');
        $response = $this->controller->delete($request, $category);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDeleteGetRequestCannotBeDeleted(): void
    {
        $category = $this->createMock(Category::class);

        $this->categoryService->expects($this->once())
            ->method('canBeDeleted')
            ->with($category)
            ->willReturn(false); // Cannot be deleted

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('warning', 'message.category_contains_event');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('category_index');

        $this->controller->expects($this->never())->method('createForm'); // Form not created if redirected
        $this->controller->expects($this->never())->method('render');

        $request = Request::create('/category/1/delete', 'GET');
        $response = $this->controller->delete($request, $category);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/mocked/redirect/category/index', $response->getTargetUrl());
    }

    public function testDeleteDeleteRequestValidFormAndCanBeDeleted(): void
    {
        $category = $this->createMock(Category::class);
        $categoryId = 1;
        $category->method('getId')->willReturn($categoryId);

        $this->categoryService->expects($this->once())
            ->method('canBeDeleted')
            ->with($category)
            ->willReturn(true); // Can be deleted

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        // FIX: Ensure form is always submitted and valid for this test case
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($this->mockFormView); // Crucial for render if this path were taken
        // FIX: Explicitly configure createForm to return *this specific* form mock for this test
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(
                FormType::class,
                $category,
                $this->callback(function($options) use ($categoryId) {
                    return $options['method'] === 'DELETE' && $options['action'] === '/mocked/url/category/delete?id=' . $categoryId;
                })
            )
            ->willReturn($form);

        $this->categoryService->expects($this->once())
            ->method('delete')
            ->with($category);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'message.deleted_successfully');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('category_index');

        $request = Request::create('/category/1/delete', 'DELETE');
        $response = $this->controller->delete($request, $category);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/mocked/redirect/category/index', $response->getTargetUrl());
    }

    public function testDeleteDeleteRequestInvalidForm(): void
    {
        $category = $this->createMock(Category::class);
        $categoryId = 1;
        $category->method('getId')->willReturn($categoryId);

        $this->categoryService->expects($this->once())
            ->method('canBeDeleted')
            ->with($category)
            ->willReturn(true); // Can be deleted

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false); // Invalid form
        $form->method('createView')->willReturn($this->mockFormView);
        // FIX: Explicitly configure createForm to return *this specific* form mock for this test
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(
                FormType::class,
                $category,
                $this->callback(function($options) use ($categoryId) {
                    return $options['method'] === 'DELETE' && $options['action'] === '/mocked/url/category/delete?id=' . $categoryId;
                })
            )
            ->willReturn($form);

        $this->categoryService->expects($this->never())->method('delete');
        $this->controller->expects($this->never())->method('addFlash');
        $this->controller->expects($this->never())->method('redirectToRoute');

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/delete.html.twig', [
                'form' => $this->mockFormView,
                'category' => $category,
            ])
            ->willReturn(new Response());

        $request = Request::create('/category/1/delete', 'DELETE');
        $response = $this->controller->delete($request, $category);

        $this->assertInstanceOf(Response::class, $response);
    }
}
