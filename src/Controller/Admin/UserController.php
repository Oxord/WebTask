<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\UserRepository;

class UserController extends AdminController
{
    private const ROLES = ['admin', 'moderator', 'user'];

    private UserRepository $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new UserRepository();
    }

    public function index(Request $request): Response
    {
        $page = $this->pageParam($request);
        $search = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', '');
        $role = in_array($role, self::ROLES, true) ? $role : '';

        $result = $this->users->paginate($page, 20, $search !== '' ? $search : null, $role !== '' ? $role : null);

        return $this->render('users/index', [
            'items' => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $page,
            'search' => $search,
            'role' => $role,
            'roles' => self::ROLES,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->render('users/form', [
            'user' => null,
            'roles' => self::ROLES,
        ]);
    }

    public function store(Request $request): Response
    {
        try {
            $data = $this->validate($request, null, true);

            $id = $this->users->create([
                'email' => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'role' => $data['role'],
                'is_active' => $data['is_active'],
            ]);

            Session::flash('success', 'Пользователь «' . $data['full_name'] . '» создан.');

            return $this->redirect('/admin/users/' . $id . '/edit');
        } catch (ValidationException $e) {
            return $this->withValidationErrors($e, $request, '/admin/users/create');
        }
    }

    public function edit(Request $request, string $id): Response
    {
        $user = $this->users->find($this->intId($id));
        if ($user === null) {
            throw new NotFoundException('Пользователь не найден.');
        }

        return $this->render('users/form', [
            'user' => $user,
            'roles' => self::ROLES,
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $userId = $this->intId($id);
        $target = $this->users->find($userId);
        if ($target === null) {
            throw new NotFoundException('Пользователь не найден.');
        }

        $isSelf = $this->auth->id() === $userId;

        try {
            $data = $this->validate($request, $userId, false);

            // Нельзя снять с себя права администратора — иначе можно случайно
            // потерять доступ ко всей админ-панели без возможности его вернуть.
            if ($isSelf && $target['role'] === 'admin' && $data['role'] !== 'admin') {
                throw new ValidationException(['role' => ['Нельзя снять с себя права администратора.']]);
            }

            $this->users->update($userId, [
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'email' => $data['email'],
                'is_active' => $data['is_active'],
            ]);
            $this->users->setRole($userId, $data['role']);

            if ($data['password'] !== null) {
                $this->users->updatePassword($userId, password_hash($data['password'], PASSWORD_BCRYPT));
            }

            Session::flash('success', 'Пользователь «' . $data['full_name'] . '» обновлён.');

            return $this->redirect('/admin/users/' . $userId . '/edit');
        } catch (ValidationException $e) {
            return $this->withValidationErrors($e, $request, '/admin/users/' . $userId . '/edit');
        }
    }

    public function destroy(Request $request, string $id): Response
    {
        $userId = $this->intId($id);
        $target = $this->users->find($userId);
        if ($target === null) {
            throw new NotFoundException('Пользователь не найден.');
        }

        if ($this->auth->id() === $userId) {
            Session::flash('error', 'Нельзя удалить собственную учётную запись.');

            return $this->redirect('/admin/users');
        }

        if ($target['role'] === 'admin') {
            $counts = $this->users->countByRole();
            if ((int) ($counts['admin'] ?? 0) <= 1) {
                Session::flash('error', 'Нельзя удалить последнего администратора.');

                return $this->redirect('/admin/users');
            }
        }

        $this->users->delete($userId);
        Session::flash('success', 'Пользователь «' . $target['full_name'] . '» удалён.');

        return $this->redirect('/admin/users');
    }

    private function validate(Request $request, ?int $exceptId, bool $passwordRequired): array
    {
        $input = $request->all();

        $rules = [
            'full_name' => 'required|min:2|max:150',
            'email' => 'required|email|max:190',
            'phone' => 'phone',
            'address' => 'max:255',
            'role' => 'required|in:' . implode(',', self::ROLES),
        ];
        $rules['password'] = $passwordRequired ? 'required|min:6' : 'min:6';

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $email = trim((string) $input['email']);
        if ($this->users->emailExists($email, $exceptId)) {
            throw new ValidationException(['email' => ['Пользователь с таким email уже существует.']]);
        }

        $password = trim((string) ($input['password'] ?? ''));

        return [
            'full_name' => trim((string) $input['full_name']),
            'email' => $email,
            'phone' => trim((string) ($input['phone'] ?? '')) ?: null,
            'address' => trim((string) ($input['address'] ?? '')) ?: null,
            'role' => (string) $input['role'],
            'is_active' => $this->checkbox($request, 'is_active'),
            'password' => $password !== '' ? $password : null,
        ];
    }
}
