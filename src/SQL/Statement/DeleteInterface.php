<?php declare(strict_types=1);

namespace DB\SQL\Statement;

use DB\SQL\Clause\ExecutableInterface;
use DB\SQL\Clause\WhereInterface;

interface DeleteInterface extends WhereInterface, ExecutableInterface
{
}
