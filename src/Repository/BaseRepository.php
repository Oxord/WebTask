<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;

abstract class BaseRepository
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }
}
