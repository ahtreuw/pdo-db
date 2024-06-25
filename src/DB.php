<?php declare(strict_types=1);

namespace DB;

use DB\SQL\CommandTrait;
use DB\SQL\SQL;
use DB\SQL\SQLInterface;
use PDO;
use PDOStatement;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

class DB implements DBInterface
{
    use CommandTrait;

    private null|PDO $pdo = null;
    private null|string $prefix = '';

    public function __construct(
        private readonly string                                     $dsn,
        private readonly string|null                                $username = null,
        private readonly string|null                                $password = null,
        private readonly array|null                                 $options = null,
        private readonly null|CacheInterface|CacheItemPoolInterface $cache = null,
        public readonly SQLInterface                                $sql = new SQL,
        private readonly FactoryInterface                           $factory = new Factory,
        private readonly LoggerInterface                            $logger = new NullLogger
    )
    {
    }

    public function prefix(null|string $prefix): DBInterface
    {
        $this->prefix = $prefix ?: '';
        return $this;
    }

    public function connect(): void
    {
        $this->logger->debug(__METHOD__, ['username' => $this->username, 'options' => $this->options]);
        $this->pdo = $this->factory->createPDO($this->dsn, $this->username, $this->password, $this->options);
    }

    public function disconnect(): void
    {
        $this->debugLog(__METHOD__);
        $this->pdo = null;
    }

    public function exec(string $query, array $parameters = []): PDOStatement
    {
        $this->debugLog(__METHOD__, ['query' => $this->prefix . $query, 'parameters' => $parameters]);

        $statement = $this->pdo()->prepare($this->prefix . $query);

        $this->prefix = '';

        foreach ($parameters as $parameter => $value) {
            $dataType = match (strtolower(gettype($value))) {
                'integer' => PDO::PARAM_INT,
                'boolean' => PDO::PARAM_BOOL,
                'null' => PDO::PARAM_NULL,
                default => PDO::PARAM_STR
            };
            $statement->bindValue($parameter, $value, $dataType);
        }

        $statement->execute();

        return $statement;
    }

    public function lastInsertId(): ?string
    {
        return $this->pdo()->lastInsertId() ?: null;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo()->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo()->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo()->inTransaction();
    }

    public function prepare(string $query, array $options): PDOStatement|false
    {
        return $this->pdo()->prepare($query, $options);
    }

    public function quote(string $string, int $type = PDO::PARAM_STR): string|false
    {
        return $this->pdo()->quote($string, $type);
    }

    public function query(string $query, int|null $fetchMode = null, ...$fetch_mode_args): PDOStatement|false
    {
        $this->debugLog(__METHOD__, [
            'query' => $query,
            'fetchMode' => $fetchMode,
            'args' => $fetch_mode_args
        ]);
        return $this->pdo()->query($query, $fetchMode, ...$fetch_mode_args);
    }

    private function pdo(): PDO
    {
        if (is_null($this->pdo)) {
            $this->connect();
        }
        return $this->pdo;
    }

    private function debugLog(string $message, array $context = []): void
    {
        if (getenv('ENVIRONMENT') !== 'development') {
            return;
        }
        $this->logger->debug($message, ['dsn' => $this->dsn] + $context);
    }
}
