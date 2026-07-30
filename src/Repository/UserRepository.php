<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

class UserRepository extends BaseRepository
{
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        if ($exceptId !== null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
            $stmt->execute([$email, $exceptId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$email]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, full_name, phone, address, role, is_active)
             VALUES (:email, :password_hash, :full_name, :phone, :address, :role, :is_active)'
        );
        $stmt->execute([
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'role' => $data['role'] ?? 'user',
            'is_active' => $data['is_active'] ?? 1,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        foreach (['full_name', 'phone', 'address', 'email', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if ($fields === []) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function updatePassword(int $id, string $hash): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');

        return $stmt->execute([$hash, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');

        return $stmt->execute([$id]);
    }

    public function paginate(int $page, int $perPage, ?string $search = null, ?string $role = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        if ($search !== null && $search !== '') {
            // Нативные prepared statements не допускают повторного использования одного плейсхолдера.
            $where[] = '(full_name LIKE :search_name OR email LIKE :search_email)';
            $params['search_name'] = '%' . $search . '%';
            $params['search_email'] = '%' . $search . '%';
        }
        if ($role !== null && $role !== '') {
            $where[] = 'role = :role';
            $params['role'] = $role;
        }
        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT * FROM users {$whereSql} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    public function countByRole(): array
    {
        $stmt = $this->pdo->query('SELECT role, COUNT(*) AS cnt FROM users GROUP BY role');
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['role']] = (int) $row['cnt'];
        }

        return $result;
    }

    public function setRole(int $id, string $role): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET role = ? WHERE id = ?');

        return $stmt->execute([$role, $id]);
    }
}
