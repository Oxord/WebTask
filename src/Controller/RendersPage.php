<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Response;
use App\Core\View;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Repository\UserRepository;
use App\Service\CartService;

/**
 * Публичные контроллеры собирают auth/cartCount здесь, а не во view: раньше это
 * делал сам layout на каждой странице (включая страницы ошибок, которые рендерит
 * public/index.php напрямую, минуя контроллеры). Если контроллер уже построил
 * $data['auth'] или $data['cartCount'] для своей бизнес-логики — переиспользуем их
 * вместо повторного похода в БД.
 */
trait RendersPage
{
    private function renderPage(string $template, array $data = [], ?string $layout = 'main'): Response
    {
        $data += [
            'auth' => new Auth(new UserRepository()),
            'cartCount' => (new CartService(new ProductRepository(), new PromotionRepository()))->count(),
        ];

        return Response::html(View::render($template, $data, $layout));
    }
}
