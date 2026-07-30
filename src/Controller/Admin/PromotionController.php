<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Service\UploadService;

class PromotionController extends AdminController
{
    private PromotionRepository $promotions;
    private ProductRepository $products;

    public function __construct()
    {
        parent::__construct();
        $this->promotions = new PromotionRepository();
        $this->products = new ProductRepository();
    }

    public function index(Request $request): Response
    {
        return $this->render('promotions/index', [
            'items' => $this->promotions->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->render('promotions/form', [
            'promotion' => null,
            'allProducts' => $this->allProducts(),
            'selectedIds' => [],
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $data = $this->validate($request, null);
            $productIds = $this->productIds($request);
            $data['image_path'] = $this->uploadImage($request);

            $id = $this->promotions->create($data);
            $this->promotions->attachProducts($id, $productIds);

            Session::flash('success', 'Акция «' . $data['title'] . '» создана.');

            return $this->redirect('/admin/promotions/' . $id . '/edit');
        } catch (ValidationException $e) {
            return $this->withValidationErrors($e, $request, '/admin/promotions/create');
        }
    }

    public function edit(Request $request, string $id): Response
    {
        $promotionId = $this->intId($id);
        $promotion = $this->promotions->find($promotionId);
        if ($promotion === null) {
            throw new NotFoundException('Акция не найдена.');
        }

        return $this->render('promotions/form', [
            'promotion' => $promotion,
            'allProducts' => $this->allProducts(),
            'selectedIds' => $this->promotions->productIds($promotionId),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $promotionId = $this->intId($id);
        $promotion = $this->promotions->find($promotionId);
        if ($promotion === null) {
            throw new NotFoundException('Акция не найдена.');
        }

        try {
            $data = $this->validate($request, $promotionId);
            $productIds = $this->productIds($request);
            $newImage = $this->uploadImage($request);
            $data['image_path'] = $newImage ?? $promotion['image_path'];

            $this->promotions->update($promotionId, $data);
            $this->promotions->attachProducts($promotionId, $productIds);

            if ($newImage !== null && !empty($promotion['image_path'])) {
                (new UploadService())->delete($promotion['image_path']);
            }

            Session::flash('success', 'Акция «' . $data['title'] . '» обновлена.');

            return $this->redirect('/admin/promotions/' . $promotionId . '/edit');
        } catch (ValidationException $e) {
            return $this->withValidationErrors($e, $request, '/admin/promotions/' . $promotionId . '/edit');
        }
    }

    public function destroy(Request $request, string $id): Response
    {
        $promotionId = $this->intId($id);
        $promotion = $this->promotions->find($promotionId);
        if ($promotion === null) {
            throw new NotFoundException('Акция не найдена.');
        }

        $this->promotions->delete($promotionId);
        if (!empty($promotion['image_path'])) {
            (new UploadService())->delete($promotion['image_path']);
        }

        Session::flash('success', 'Акция «' . $promotion['title'] . '» удалена.');

        return $this->redirect('/admin/promotions');
    }

    private function validate(Request $request, ?int $exceptId): array
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'title' => 'required|min:2|max:200',
            'description' => 'max:5000',
            'discount_percent' => 'required|int|min:1|max:99',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        if (strtotime((string) $input['ends_at']) <= strtotime((string) $input['starts_at'])) {
            throw new ValidationException(['ends_at' => ['Дата окончания должна быть позже даты начала.']]);
        }

        $title = trim((string) $input['title']);
        $slugInput = trim((string) ($input['slug'] ?? ''));
        $slug = slugify($slugInput !== '' ? $slugInput : $title);

        $existing = $this->promotions->findBySlug($slug);
        if ($existing !== null && (int) $existing['id'] !== (int) $exceptId) {
            throw new ValidationException(['slug' => ['Акция с таким адресом (slug) уже существует.']]);
        }

        return [
            'title' => $title,
            'slug' => $slug,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'discount_percent' => (int) $input['discount_percent'],
            'starts_at' => date('Y-m-d H:i:s', strtotime((string) $input['starts_at'])),
            'ends_at' => date('Y-m-d H:i:s', strtotime((string) $input['ends_at'])),
            'is_active' => $this->checkbox($request, 'is_active'),
        ];
    }

    private function productIds(Request $request): array
    {
        $ids = $request->input('product_ids', []);
        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function uploadImage(Request $request): ?string
    {
        $file = $request->file('image');
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return (new UploadService())->storeImage($file, 'promotions');
    }

    // Полный список товаров для мультиселекта — своя выборка через paginateAdmin
    // (без фильтра активности), т.к. в репозитории нет отдельного метода all().
    private function allProducts(): array
    {
        return $this->products->paginateAdmin(1, 1000)['items'];
    }
}
