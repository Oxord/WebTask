<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Service\CartService;

class CartController
{
    use RendersPage;

    public function index(Request $request): Response
    {
        $cart = $this->cartService();

        return $this->renderPage('pages/cart/index', [
            'pageTitle' => 'Корзина',
            'items' => $cart->items(),
            'totals' => $cart->totals(),
            'cartCount' => $cart->count(),
        ]);
    }

    public function add(Request $request): Response
    {
        $cart = $this->cartService();
        $productId = (int) $request->input('product_id', 0);
        $qty = max(1, (int) $request->input('qty', 1));

        if ($productId > 0) {
            $cart->add($productId, $qty);
            Session::flash('success', 'Товар добавлен в корзину.');
        }

        if ($request->isAjax()) {
            return Response::json([
                'ok' => true,
                'count' => $cart->count(),
                'totals' => $cart->totals(),
                'message' => 'Товар добавлен в корзину.',
            ]);
        }

        return Response::redirect($this->backTo($request, '/cart'));
    }

    public function update(Request $request): Response
    {
        $cart = $this->cartService();
        $productId = (int) $request->input('product_id', 0);
        $qty = (int) $request->input('qty', 1);

        if ($productId > 0) {
            $cart->update($productId, $qty);
            Session::flash('success', 'Количество обновлено.');
        }

        if ($request->isAjax()) {
            return Response::json([
                'ok' => true,
                'count' => $cart->count(),
                'totals' => $cart->totals(),
                'items' => $cart->items(),
            ]);
        }

        return Response::redirect(url('/cart'));
    }

    public function remove(Request $request): Response
    {
        $cart = $this->cartService();
        $productId = (int) $request->input('product_id', 0);

        if ($productId > 0) {
            $cart->remove($productId);
            Session::flash('success', 'Товар удалён из корзины.');
        }

        if ($request->isAjax()) {
            return Response::json([
                'ok' => true,
                'count' => $cart->count(),
                'totals' => $cart->totals(),
            ]);
        }

        return Response::redirect(url('/cart'));
    }

    public function clear(Request $request): Response
    {
        $cart = $this->cartService();
        $cart->clear();
        Session::flash('info', 'Корзина очищена.');

        return Response::redirect(url('/cart'));
    }

    private function cartService(): CartService
    {
        return new CartService(new ProductRepository(), new PromotionRepository());
    }

    private function backTo(Request $request, string $default): string
    {
        $referer = $request->header('Referer') ?? '';
        $path = (string) (parse_url($referer, PHP_URL_PATH) ?: '');
        $query = (string) (parse_url($referer, PHP_URL_QUERY) ?: '');

        if ($path !== '' && str_starts_with($path, '/') && !str_starts_with($path, '//')) {
            return $path . ($query !== '' ? '?' . $query : '');
        }

        return url($default);
    }
}
