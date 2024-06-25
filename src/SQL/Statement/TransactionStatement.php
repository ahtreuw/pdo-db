<?php declare(strict_types=1);

namespace DB\SQL\Statement;

use Closure;
use DB\DBInterface;
use PDOException;
use Throwable;

readonly class TransactionStatement implements TransactionInterface
{
    const DEFAULT_ATTEMPT_NO = 10;
    const DEFAULT_SLEEP_TIME = .2;

    public function __construct(
        private Closure $closure,
        private float   $sleep = self::DEFAULT_SLEEP_TIME,
        private int     $attemptNo = self::DEFAULT_ATTEMPT_NO
    )
    {
    }

    public function exec(DBInterface $db): mixed
    {
        $exception = null;
        for ($i = 0; $i < $this->attemptNo; $i++) {
            try {
                $db->beginTransaction();
                $result = ($this->closure)();
                $db->commit();
                return $result;
            } catch (Throwable $exception) {
                $db->rollBack();
                $this->restart($exception);
            }
        }
        throw $exception;
    }

    /**
     * @throws Throwable
     */
    private function restart(Throwable $exception): void
    {
        if ($exception instanceof PDOException === false) {
            throw $exception;
        }
        if (
            $exception->getCode() != 40001 &&
            !str_contains($exception->getMessage(), 'try restarting transaction')
        ) {
            throw $exception;
        }
        if (0 < $this->sleep) {
            usleep($this->sleep * 1000000);
        }
    }
}