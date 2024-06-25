<?php declare(strict_types=1);

namespace DB\SQL\Statement;

use DB\DBInterface;
use Throwable;

interface TransactionInterface
{
    /**
     * @throws Throwable
     */
    public function exec(DBInterface $db): mixed;
}