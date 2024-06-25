<?php declare(strict_types=1);

namespace DB\SQL\Clause;

use DB\DBInterface;
use PDOStatement;
use Throwable;

interface ExecutableInterface
{

    /**
     * @throws Throwable
     */
    public function exec(DBInterface $db): PDOStatement;
}