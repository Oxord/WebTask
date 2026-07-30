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

class CategoryController extends AdminController
{
    private CategoryRepository $categories;

    public function __construct()
    {
        parent::__construct();
        $this->categories = new CategoryRepository();
    }

    public function index(Request $request): Response
    {
        return $this->render('categories/index', [
            'items' => $this->categories->withProductCounts(),
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $data = $this->validate($request, null);
            $this->categories->create($data);
            Session::flash('success', 'Категория «' . $data['name'] . '» создана.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    Session::flash('error', $message);
                }
            }
        }

        return $this->redirect('/admin/categories');
    }

    public function update(Request $request, string $id): Response
    {
        $categoryId = $this->intId($id);
        $category = $this->categories->find($categoryId);
        if ($category === null) {
            throw new NotFoundException('Категория не найдена.');
        }

        try {
            $data = $this->validate($request, $categoryId);
            $this->categories->update($categoryId, $data);
            Session::flash('success', 'Категория «' . $data['name'] . '» обновлена.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    Session::flash('error', $message);
                }
            }
        }

        return $this->redirect('/admin/categories');
    }

    public function destroy(Request $request, string $id): Response
    {
        $categoryId = $this->intId($id);
        $category = $this->categories->find($categoryId);
        if ($category === null) {
            throw new NotFoundException('Категория не найдена.');
        }

        // Внешний ключ products.category_id -> ON DELETE SET NULL: товары останутся без категории.
        $this->categories->delete($categoryId);
        Session::flash('success', 'Категория «' . $category['name'] . '» удалена. Товары этой категории остались без категории.');

        return $this->redirect('/admin/categories');
    }

    private function validate(Request $request, ?int $exceptId): array
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required|min:2|max:120',
            'description' => 'max:2000',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $name = trim((string) $input['name']);
        $slugInput = trim((string) ($input['slug'] ?? ''));
        $slug = slugify($slugInput !== '' ? $slugInput : $name);

        $existing = $this->categories->findBySlug($slug);
        if ($existing !== null && (int) $existing['id'] !== (int) $exceptId) {
            throw new ValidationException(['slug' => ['Категория с таким адресом (slug) уже существует.']]);
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'sort_order' => (int) ($input['sort_order'] ?? 0),
        ];
    }
}
