<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Exception\NotFoundException;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;

class PromotionController
{
    use RendersPage;

    public function index(Request $request): Response
    {
        $promotions = new PromotionRepository();
        $all = $promotions->all();

        $active = [];
        $completed = [];
        foreach ($all as $promo) {
            if (self::isRunning($promo)) {
                $active[] = $promo;
            } else {
                $completed[] = $promo;
            }
        }

        return $this->renderPage('pages/promotions/index', [
            'pageTitle' => 'Акции и спецпредложения',
            'activePromotions' => $active,
            'completedPromotions' => $completed,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $promotions = new PromotionRepository();
        $promotion = $promotions->findBySlug($slug);
        if ($promotion === null) {
            throw new NotFoundException('Акция не найдена');
        }

        $productIds = $promotions->productIds((int) $promotion['id']);
        $products = (new ProductRepository())->findMany($productIds);

        return $this->renderPage('pages/promotions/show', [
            'pageTitle' => $promotion['title'],
            'promotion' => $promotion,
            'products' => $products,
            'isActive' => self::isRunning($promotion),
        ]);
    }

    /**
     * Единственное место, где проверяется «акция идёт сейчас» для публичной части.
     * В админском шаблоне (views/admin/promotions/index.php) осталась независимая
     * копия этой же проверки — админские view вне зоны этой правки.
     */
    public static function isRunning(array $promotion): bool
    {
        $now = time();

        return (bool) $promotion['is_active']
            && $now >= strtotime((string) $promotion['starts_at'])
            && $now <= strtotime((string) $promotion['ends_at']);
    }
}
