<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Config;
use App\Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that hit the real test database (db_test / decor_test).
 *
 * Isolation strategy: by default each test runs inside an outer transaction that is
 * rolled back in tearDown() (see NestedTransactionPdo for how nested app-level
 * transactions are handled via SAVEPOINT). This is fast and leaves zero residue.
 *
 * One exception: InnoDB FULLTEXT indexes are only synced to the on-disk index at COMMIT
 * time, so rows inserted-but-not-committed are invisible to MATCH() ... AGAINST() queries
 * run on the very same (still open) transaction. Any test class that needs to exercise
 * FULLTEXT search (currently only ProductRepositoryTest) must override isTransactional()
 * to return false; such classes fall back to real autocommit inserts and manually wipe
 * the tables before/after every test via cleanDatabase().
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static PDO $pdo;

    private static int $seq = 0;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = self::connect();
    }

    public static function tearDownAfterClass(): void
    {
        Database::reset();
    }

    protected function setUp(): void
    {
        $_SESSION = [];
        Database::setConnection(self::$pdo);

        if ($this->isTransactional()) {
            self::$pdo->beginTransaction();
        } else {
            $this->cleanDatabase();
        }
    }

    protected function tearDown(): void
    {
        if ($this->isTransactional()) {
            if (self::$pdo->inTransaction()) {
                self::$pdo->rollBack();
            }
        } else {
            $this->cleanDatabase();
        }

        Database::reset();
        $_SESSION = [];
    }

    /**
     * Override and return false for tests that depend on FULLTEXT search seeing
     * committed data within the same test.
     */
    protected function isTransactional(): bool
    {
        return true;
    }

    private static function connect(): PDO
    {
        $host = (string) Config::get('TEST_DB_HOST', 'db_test');
        $port = (string) Config::get('DB_PORT', '3306');
        $name = (string) Config::get('TEST_DB_NAME', 'decor_test');
        $user = (string) Config::get('DB_USER', 'decor');
        $pass = (string) Config::get('DB_PASS', 'decor_secret');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        return new NestedTransactionPdo($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    /**
     * Wipes every table in FK-safe (children-first) order. Used for non-transactional
     * test classes to guarantee isolation without relying on rollback.
     */
    protected function cleanDatabase(): void
    {
        $pdo = self::$pdo;
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ([
            'order_items',
            'promotion_products',
            'reviews',
            'orders',
            'promotions',
            'products',
            'categories',
            'contact_messages',
            'users',
        ] as $table) {
            $pdo->exec("DELETE FROM {$table}");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function seq(): int
    {
        return ++self::$seq;
    }

    protected function fetchRow(string $table, int $id): array
    {
        $stmt = self::$pdo->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        self::assertIsArray($row, "Fixture row not found in {$table}#{$id}");

        return $row;
    }

    // -----------------------------------------------------------------
    // Fixture factories — deliberately use raw SQL rather than the
    // repositories under test, so fixtures don't depend on the code being verified.
    // -----------------------------------------------------------------

    protected function createUser(array $overrides = []): array
    {
        $n = $this->seq();
        $passwordPlain = $overrides['password'] ?? 'secret123';

        $stmt = self::$pdo->prepare(
            'INSERT INTO users (email, password_hash, full_name, phone, address, role, is_active)
             VALUES (:email, :password_hash, :full_name, :phone, :address, :role, :is_active)'
        );
        $stmt->execute([
            'email' => $overrides['email'] ?? "user{$n}@example.test",
            'password_hash' => $overrides['password_hash'] ?? password_hash((string) $passwordPlain, PASSWORD_BCRYPT),
            'full_name' => $overrides['full_name'] ?? "Тестовый Пользователь {$n}",
            'phone' => $overrides['phone'] ?? '+7 900 000-00-01',
            'address' => $overrides['address'] ?? "г. Москва, ул. Тестовая, д. {$n}",
            'role' => $overrides['role'] ?? 'user',
            'is_active' => $overrides['is_active'] ?? 1,
        ]);

        return $this->fetchRow('users', (int) self::$pdo->lastInsertId());
    }

    protected function createCategory(array $overrides = []): array
    {
        $n = $this->seq();

        $stmt = self::$pdo->prepare(
            'INSERT INTO categories (name, slug, description, sort_order)
             VALUES (:name, :slug, :description, :sort_order)'
        );
        $stmt->execute([
            'name' => $overrides['name'] ?? "Категория {$n}",
            'slug' => $overrides['slug'] ?? "category-{$n}",
            'description' => $overrides['description'] ?? 'Тестовая категория',
            'sort_order' => $overrides['sort_order'] ?? 0,
        ]);

        return $this->fetchRow('categories', (int) self::$pdo->lastInsertId());
    }

    protected function createProduct(array $overrides = []): array
    {
        $n = $this->seq();
        $categoryId = array_key_exists('category_id', $overrides)
            ? $overrides['category_id']
            : $this->createCategory()['id'];

        $stmt = self::$pdo->prepare(
            'INSERT INTO products (category_id, name, slug, description, price, image_path, stock, is_featured, is_active)
             VALUES (:category_id, :name, :slug, :description, :price, :image_path, :stock, :is_featured, :is_active)'
        );
        $stmt->execute([
            'category_id' => $categoryId,
            'name' => $overrides['name'] ?? "Товар {$n}",
            'slug' => $overrides['slug'] ?? "product-{$n}",
            'description' => $overrides['description'] ?? "Описание тестового товара номер {$n}",
            'price' => $overrides['price'] ?? '1000.00',
            'image_path' => $overrides['image_path'] ?? null,
            'stock' => $overrides['stock'] ?? 10,
            'is_featured' => $overrides['is_featured'] ?? 0,
            'is_active' => $overrides['is_active'] ?? 1,
        ]);

        return $this->fetchRow('products', (int) self::$pdo->lastInsertId());
    }

    protected function createPromotion(array $overrides = []): array
    {
        $n = $this->seq();
        $startsAt = $overrides['starts_at'] ?? date('Y-m-d H:i:s', strtotime('-1 day'));
        $endsAt = $overrides['ends_at'] ?? date('Y-m-d H:i:s', strtotime('+7 days'));

        $stmt = self::$pdo->prepare(
            'INSERT INTO promotions (title, slug, description, discount_percent, image_path, starts_at, ends_at, is_active)
             VALUES (:title, :slug, :description, :discount_percent, :image_path, :starts_at, :ends_at, :is_active)'
        );
        $stmt->execute([
            'title' => $overrides['title'] ?? "Акция {$n}",
            'slug' => $overrides['slug'] ?? "promo-{$n}",
            'description' => $overrides['description'] ?? 'Тестовая акция',
            'discount_percent' => $overrides['discount_percent'] ?? 10,
            'image_path' => $overrides['image_path'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => $overrides['is_active'] ?? 1,
        ]);

        return $this->fetchRow('promotions', (int) self::$pdo->lastInsertId());
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    protected function createOrder(array $overrides = [], array $items = []): array
    {
        $n = $this->seq();

        $stmt = self::$pdo->prepare(
            'INSERT INTO orders (user_id, order_number, status, subtotal, discount_total, total,
             customer_name, customer_email, customer_phone, shipping_address, comment)
             VALUES (:user_id, :order_number, :status, :subtotal, :discount_total, :total,
             :customer_name, :customer_email, :customer_phone, :shipping_address, :comment)'
        );
        $stmt->execute([
            'user_id' => $overrides['user_id'] ?? null,
            'order_number' => $overrides['order_number'] ?? sprintf('DH-%s-%04d', date('Ymd'), $n % 10000),
            'status' => $overrides['status'] ?? 'new',
            'subtotal' => $overrides['subtotal'] ?? '1000.00',
            'discount_total' => $overrides['discount_total'] ?? '0.00',
            'total' => $overrides['total'] ?? '1000.00',
            'customer_name' => $overrides['customer_name'] ?? 'Иван Иванов',
            'customer_email' => $overrides['customer_email'] ?? "customer{$n}@example.test",
            'customer_phone' => $overrides['customer_phone'] ?? '+7 999 123-45-67',
            'shipping_address' => $overrides['shipping_address'] ?? 'г. Москва, ул. Тестовая, д. 1',
            'comment' => $overrides['comment'] ?? null,
        ]);
        $orderId = (int) self::$pdo->lastInsertId();

        if ($items === []) {
            $items = [[
                'product_id' => null,
                'product_name' => 'Тестовый товар',
                'unit_price' => '1000.00',
                'discount_percent' => 0,
                'qty' => 1,
                'line_total' => '1000.00',
            ]];
        }

        $itemStmt = self::$pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, unit_price, discount_percent, qty, line_total)
             VALUES (:order_id, :product_id, :product_name, :unit_price, :discount_percent, :qty, :line_total)'
        );
        foreach ($items as $item) {
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'] ?? 'Тестовый товар',
                'unit_price' => $item['unit_price'] ?? '1000.00',
                'discount_percent' => $item['discount_percent'] ?? 0,
                'qty' => $item['qty'] ?? 1,
                'line_total' => $item['line_total'] ?? '1000.00',
            ]);
        }

        return $this->fetchRow('orders', $orderId);
    }

    protected function createReview(array $overrides = []): array
    {
        $userId = $overrides['user_id'] ?? $this->createUser()['id'];
        $productId = array_key_exists('product_id', $overrides)
            ? $overrides['product_id']
            : $this->createProduct()['id'];

        $stmt = self::$pdo->prepare(
            'INSERT INTO reviews (user_id, product_id, rating, body, status, is_featured, moderated_by, moderated_at)
             VALUES (:user_id, :product_id, :rating, :body, :status, :is_featured, :moderated_by, :moderated_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'product_id' => $productId,
            'rating' => $overrides['rating'] ?? 5,
            'body' => $overrides['body'] ?? 'Отличный товар, всем рекомендую эту покупку для дома!',
            'status' => $overrides['status'] ?? 'pending',
            'is_featured' => $overrides['is_featured'] ?? 0,
            'moderated_by' => $overrides['moderated_by'] ?? null,
            'moderated_at' => $overrides['moderated_at'] ?? null,
        ]);

        return $this->fetchRow('reviews', (int) self::$pdo->lastInsertId());
    }

    protected function createContactMessage(array $overrides = []): array
    {
        $n = $this->seq();

        $stmt = self::$pdo->prepare(
            'INSERT INTO contact_messages (name, email, phone, message, is_read) VALUES (:name, :email, :phone, :message, :is_read)'
        );
        $stmt->execute([
            'name' => $overrides['name'] ?? "Гость {$n}",
            'email' => $overrides['email'] ?? "guest{$n}@example.test",
            'phone' => $overrides['phone'] ?? '+7 900 111-22-33',
            'message' => $overrides['message'] ?? 'Тестовое сообщение из формы обратной связи.',
            'is_read' => $overrides['is_read'] ?? 0,
        ]);

        return $this->fetchRow('contact_messages', (int) self::$pdo->lastInsertId());
    }
}
