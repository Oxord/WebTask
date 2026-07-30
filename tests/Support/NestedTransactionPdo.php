<?php

declare(strict_types=1);

namespace Tests\Support;

use PDO;

/**
 * PDO subclass that turns a *nested* beginTransaction()/commit()/rollBack() call into a
 * MySQL SAVEPOINT instead of letting it hit the driver as a second top-level transaction.
 *
 * Why this exists: several repositories (OrderRepository::create(),
 * PromotionRepository::attachProducts()) open their own top-level transaction internally.
 * IntegrationTestCase wraps every test in an outer transaction for isolation/rollback.
 * MySQL/PDO do not support nested "real" transactions — a second beginTransaction() on an
 * already-active connection throws "There is already an active transaction". Wrapping the
 * inner call in a SAVEPOINT lets the application code keep using plain
 * beginTransaction()/commit()/rollBack() with no test-only special casing, while the outer
 * test transaction can still be rolled back in full to leave the database clean.
 */
final class NestedTransactionPdo extends PDO
{
    private int $savepointLevel = 0;

    public function beginTransaction(): bool
    {
        if ($this->inTransaction()) {
            $this->savepointLevel++;
            $this->exec('SAVEPOINT trans_' . $this->savepointLevel);

            return true;
        }

        return parent::beginTransaction();
    }

    public function commit(): bool
    {
        if ($this->savepointLevel > 0) {
            $this->exec('RELEASE SAVEPOINT trans_' . $this->savepointLevel);
            $this->savepointLevel--;

            return true;
        }

        return parent::commit();
    }

    public function rollBack(): bool
    {
        if ($this->savepointLevel > 0) {
            $this->exec('ROLLBACK TO SAVEPOINT trans_' . $this->savepointLevel);
            $this->savepointLevel--;

            return true;
        }

        return parent::rollBack();
    }
}
