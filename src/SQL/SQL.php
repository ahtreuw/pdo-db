<?php declare(strict_types=1);

namespace DB\SQL;

readonly class SQL implements SQLInterface
{

    public function __construct(private string $sql = '', private array $parameters = [])
    {
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function __get(string $sql): SQLInterface
    {
        return new SQL($sql);
    }

    public function __call(string $name, array $arguments)
    {
        return new SQL(sprintf("%s(%s)", $name, $arguments[0] ?? ''));
    }

    public function __invoke($sql, array $parameters = []): SQLInterface
    {
        return new SQL($sql, $parameters);
    }

    public function __toString(): string
    {
        return $this->sql;
    }
}
