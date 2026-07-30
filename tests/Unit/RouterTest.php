<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

final class RouterTestStaticController
{
    public function index(Request $request): Response
    {
        return Response::html('static-ok');
    }
}

final class RouterTestSlugController
{
    public function show(Request $request, string $slug): Response
    {
        return Response::html('slug:' . $slug);
    }
}

final class RouterTestIdController
{
    public function show(Request $request, string $id): Response
    {
        return Response::html('id:' . $id);
    }
}

final class RouterTestPostController
{
    public function store(Request $request): Response
    {
        return Response::html('post-ok');
    }
}

final class RouterTest extends TestCase
{
    public function testStaticRouteMatches(): void
    {
        $router = new Router();
        $router->get('/about', [RouterTestStaticController::class, 'index']);

        $response = $router->dispatch(Request::create('GET', '/about'));

        self::assertSame('static-ok', $response->body());
    }

    public function testSlugPlaceholderMatches(): void
    {
        $router = new Router();
        $router->get('/products/{slug}', [RouterTestSlugController::class, 'show']);

        $response = $router->dispatch(Request::create('GET', '/products/vaza-steklo'));

        self::assertSame('slug:vaza-steklo', $response->body());
    }

    public function testIdPlaceholderMatchesDigitsOnly(): void
    {
        $router = new Router();
        $router->get('/orders/{id}', [RouterTestIdController::class, 'show']);

        $response = $router->dispatch(Request::create('GET', '/orders/42'));

        self::assertSame('id:42', $response->body());
    }

    public function testIdPlaceholderRejectsNonDigits(): void
    {
        $router = new Router();
        $router->get('/orders/{id}', [RouterTestIdController::class, 'show']);

        $this->expectException(NotFoundException::class);
        $router->dispatch(Request::create('GET', '/orders/abc'));
    }

    public function testGetAndPostAreDistinguished(): void
    {
        $router = new Router();
        $router->get('/items', [RouterTestStaticController::class, 'index']);
        $router->post('/items', [RouterTestPostController::class, 'store']);

        $getResponse = $router->dispatch(Request::create('GET', '/items'));
        $postResponse = $router->dispatch(Request::create('POST', '/items'));

        self::assertSame('static-ok', $getResponse->body());
        self::assertSame('post-ok', $postResponse->body());
    }

    public function testUnknownRouteThrowsNotFound(): void
    {
        $router = new Router();

        $this->expectException(NotFoundException::class);
        $router->dispatch(Request::create('GET', '/nowhere'));
    }

    public function testMiddlewareShortCircuitsChain(): void
    {
        $router = new Router();
        $blocked = static fn (Request $request): Response => Response::redirect('/login');
        $router->get('/secret', [RouterTestStaticController::class, 'index'], [$blocked]);

        $response = $router->dispatch(Request::create('GET', '/secret'));

        self::assertSame(302, $response->status());
        self::assertSame('/login', $response->headers()['Location']);
    }

    public function testMiddlewarePassesThroughWhenReturningNull(): void
    {
        $router = new Router();
        $passthrough = static fn (Request $request): ?Response => null;
        $router->get('/open', [RouterTestStaticController::class, 'index'], [$passthrough]);

        $response = $router->dispatch(Request::create('GET', '/open'));

        self::assertSame('static-ok', $response->body());
    }
}
