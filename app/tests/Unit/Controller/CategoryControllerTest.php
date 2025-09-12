<?php

/**
 * Category controller Test.
 */

namespace App\Tests\Unit\Controller;

use App\Controller\CategoryController;
use App\Entity\Category;
use App\Form\Type\CategoryType;
use App\Service\CategoryServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Class Category controller Test.
 */
class CategoryControllerTest extends TestCase
{
    private MockObject|CategoryServiceInterface $categoryService;
    private MockObject|CategoryController $controller;
    private MockObject|FormView $mockFormView;

    /**
     * Set up.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryService = $this->createMock(CategoryServiceInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $this->mockFormView = $this->createMock(FormView::class);

        $this->controller = $this->getMockBuilder(CategoryController::class)
            ->setConstructorArgs([$this->categoryService, $translator])
            ->setMethods(['createForm', 'render', 'redirectToRoute', 'addFlash', 'generateUrl'])
            ->getMock();

        $this->controller->method('createForm')->willReturnCallback(function () {
            $formMock = $this->createMock(FormInterface::class);
            $formMock->method('handleRequest')->willReturnSelf();
            $formMock->method('createView')->willReturn($this->mockFormView);
            // Default behavior: not submitted, not valid.
            $formMock->method('isSubmitted')->willReturn(false);
            $formMock->method('isValid')->willReturn(false);

            return $formMock;
        });


        $this->controller->method('render')->willReturn(new Response());

        $this->controller->method('redirectToRoute')->willReturnCallback(function ($route, $params = [], $status = 302) {
            $url = '/mocked/redirect/'.str_replace('_', '/', $route);
            if (!empty($params)) {
                $url .= '?'.http_build_query($params);
            }

            return new RedirectResponse($url, $status);
        });

        $this->controller->method('addFlash');

        $this->controller->method('generateUrl')->willReturnCallback(function ($route, $params = []) {
            $url = '/mocked/url/'.str_replace('_', '/', $route);
            if (!empty($params)) {
                $url .= '?'.http_build_query($params);
            }

            return $url;
        });

        $translator->method('trans')->willReturnArgument(0);
    }

    /**
     * Test index.
     */
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

        Request::create('/category');
        $this->controller->index($page);
    }

    /**
     * Test index with default page.
     */
    public function testIndexWithDefaultPage(): void
    {
        $pagination = $this->createMock(PaginationInterface::class);

        $this->categoryService->expects($this->once())
            ->method('getPaginatedList')
            ->with(1)
            ->willReturn($pagination);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/index.html.twig', ['pagination' => $pagination])
            ->willReturn(new Response());

        Request::create('/category');
        $this->controller->index();
    }

    /**
     * Test view.
     */
    public function testView(): void
    {
        $category = $this->createMock(Category::class);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/view.html.twig', ['category' => $category])
            ->willReturn(new Response());

        $this->controller->view($category);
    }

    /**
     * Test create get request renders form.
     */
    public function testCreateGetRequestRendersForm(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(CategoryType::class, $this->isInstanceOf(Category::class))
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/create.html.twig', ['form' => $this->mockFormView])
            ->willReturn(new Response());

        $request = Request::create('/category/create');
        $this->controller->create($request);
    }

    /**
     * Test create post request invalid form.
     */
    public function testCreatePostRequestInvalidForm(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
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

        $request = Request::create('/category/create', \Symfony\Component\HttpFoundation\Request::METHOD_POST, ['title' => '']);
        $this->controller->create($request);
    }

    /**
     * Test edit get request renders form.
     */
    public function testEditGetRequestRendersForm(): void
    {
        $category = $this->createMock(Category::class);
        $categoryId = 1;
        $category->method('getId')->willReturn($categoryId);

        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->expects($this->once())
        ->method('createForm')
            ->with(CategoryType::class, $category, $this->callback(fn($options) => 'PUT' === $options['method'] && $options['action'] === '/mocked/url/category/edit?id='.$categoryId))
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('render')
            ->with('category/edit.html.twig', [
                'form' => $this->mockFormView,
                'category' => $category,
            ])
            ->willReturn(new Response());

        $request = Request::create('/category/1/edit');
        $this->controller->edit($request, $category);
    }

    /**
     * Test edit put request invalid form.
     */
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
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(
                CategoryType::class,
                $category,
                $this->callback(fn($options) => 'PUT' === $options['method'] && $options['action'] === '/mocked/url/category/edit?id='.$categoryId)
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

        $request = Request::create('/category/1/edit', \Symfony\Component\HttpFoundation\Request::METHOD_PUT, ['title' => '']);
        $this->controller->edit($request, $category);
    }

    /**
     * Test delete get request can be deleted.
     */
    public function testDeleteGetRequestCanBeDeleted(): void
    {
        $category = $this->createMock(Category::class);
        $categoryId = 1;
        $category->method('getId')->willReturn($categoryId);

        $this->categoryService->expects($this->once())
            ->method('canBeDeleted')
            ->with($category)
            ->willReturn(true);

        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->expects($this->once())
        ->method('createForm')
            ->with(FormType::class, $category, $this->callback(fn($options) => 'DELETE' === $options['method'] && $options['action'] === '/mocked/url/category/delete?id='.$categoryId))
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

        $request = Request::create('/category/1/delete');
        $this->controller->delete($request, $category);
    }

    /**
     * Test delete get request cannot be deleted.
     */
    public function testDeleteGetRequestCannotBeDeleted(): void
    {
        $category = $this->createMock(Category::class);

        $this->categoryService->expects($this->once())
            ->method('canBeDeleted')
            ->with($category)
            ->willReturn(false);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('warning', 'message.category_contains_event');

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('category_index');

        $this->controller->expects($this->never())->method('createForm');
        $this->controller->expects($this->never())->method('render');

        $request = Request::create('/category/1/delete');
        $response = $this->controller->delete($request, $category);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/mocked/redirect/category/index', $response->getTargetUrl());
    }

    /**
     * Test delete request invalid form.
     */
    public function testDeleteDeleteRequestInvalidForm(): void
    {
        $category = $this->createMock(Category::class);
        $categoryId = 1;
        $category->method('getId')->willReturn($categoryId);

        $this->categoryService->expects($this->once())
            ->method('canBeDeleted')
            ->with($category)
            ->willReturn(true);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(false);
        $form->method('createView')->willReturn($this->mockFormView);
        $this->controller->expects($this->once())
            ->method('createForm')
            ->with(
                FormType::class,
                $category,
                $this->callback(fn($options) => 'DELETE' === $options['method'] && $options['action'] === '/mocked/url/category/delete?id='.$categoryId)
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

        $request = Request::create('/category/1/delete', \Symfony\Component\HttpFoundation\Request::METHOD_DELETE);
        $this->controller->delete($request, $category);
    }
}
