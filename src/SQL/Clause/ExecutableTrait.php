<?php declare(strict_types=1);

namespace DB\SQL\Clause;

use DB\DBInterface;
use PDOStatement;

trait ExecutableTrait
{
    public function exec(DBInterface $db): PDOStatement
    {
        if (false === $this->useTransaction) {
            return $db->exec($this->__toString(), $this->getParameters());
        }

        return $db->transaction(function () use ($db) {
            return $db->exec($this->__toString(), $this->getParameters());
        });
    }
}
