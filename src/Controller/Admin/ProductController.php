<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\UploadService;
use PDO;

class ProductController extends AdminController
{
    private ProductRepository $products;
    private CategoryRepository $categories;

    public function __construct()
    {
        parent::__construct();
        $this->products = new ProductRepository();
        $this->categories = new CategoryRepository();
    }

    public function index(Request $request): Response
    {
        $page = $this->pageParam($request);
        $search = trim((string) $request->query('q', ''));
        $categoryId = (int) $request->query('category_id', 0);

        $result = $this->products->paginateAdmin($page, 20, $search, $categoryId);

        $categoriesById = [];
        foreach ($this->categories->all() as $category) {
            $categoriesById[(int) $category['id']] = $category['name'];
        }

        return $this->render('products/index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $page,
            'search' => $search,
            'categoryId' => $categoryId,
            'categories' => $this->categories->all(),
            'categoriesById' => $categoriesById,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->render('products/form', [
            'product' => null,
            'categories' => $this->categories->all(),
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $data = $this->validate($request, null);
            $data['image_path'] = $this->uploadImage($request);

            $id = $this->products->create($data);
            Session::flash('success', 'Товар «' . $data['name'] . '» создан.');

            return $this->redirect('/admin/products/' . $id . '/edit');
        } catch (ValidationException $e) {
            return $this->withValidationErrors($e, $request, '/admin/products/create');
        }
    }

    public function edit(Request $request, string $id): Response
    {
        $product = $this->products->find($this->intId($id));
        if ($product === null) {
            throw new NotFoundException('Товар не найден.');
        }

        return $this->render('products/form', [
            'product' => $product,
            'categories' => $this->categories->all(),
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $productId = $this->intId($id);
        $product = $this->products->find($productId);
        if ($product === null) {
            throw new NotFoundException('Товар не найден.');
        }

        try {
            $data = $this->validate($request, $productId);
            $newImage = $this->uploadImage($request);
            $data['image_path'] = $newImage ?? $product['image_path'];

            $this->products->update($productId, $data);

            if ($newImage !== null && !empty($product['image_path'])) {
                (new UploadService())->delete($product['image_path']);
            }

            Session::flash('success', 'Товар «' . $data['name'] . '» обновлён.');

            return $this->redirect('/admin/products/' . $productId . '/edit');
        } catch (ValidationException $e) {
            return $this->withValidationErrors($e, $request, '/admin/products/' . $productId . '/edit');
        }
    }

    public function destroy(Request $request, string $id): Response
    {
        $productId = $this->intId($id);
        $product = $this->products->find($productId);
        if ($product === null) {
            throw new NotFoundException('Товар не найден.');
        }

        $this->products->delete($productId);
        if (!empty($product['image_path'])) {
            (new UploadService())->delete($product['image_path']);
        }

        Session::flash('success', 'Товар «' . $product['name'] . '» удалён.');

        return $this->redirect('/admin/products');
    }

    private function validate(Request $request, ?int $exceptId): array
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required|min:2|max:200',
            'category_id' => 'int',
            'description' => 'max:5000',
            'price' => 'required|numeric|min:0.01',
            'stock' => 'required|int|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $name = trim((string) $input['name']);
        $slugInput = trim((string) ($input['slug'] ?? ''));
        $slug = slugify($slugInput !== '' ? $slugInput : $name);

        $existing = $this->products->findBySlug($slug);
        if ($existing !== null && (int) $existing['id'] !== (int) $exceptId) {
            throw new ValidationException(['slug' => ['Товар с таким адресом (slug) уже существует.']]);
        }

        $categoryId = trim((string) ($input['category_id'] ?? ''));

        return [
            'name' => $name,
            'slug' => $slug,
            'category_id' => $categoryId !== '' ? (int) $categoryId : null,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'price' => (float) $input['price'],
            'stock' => (int) $input['stock'],
            'is_featured' => $this->checkbox($request, 'is_featured'),
            'is_active' => $this->checkbox($request, 'is_active'),
        ];
    }

    private function uploadImage(Request $request): ?string
    {
        $file = $request->file('image');
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return (new UploadService())->storeImage($file, 'products');
    }
}
