<?php declare(strict_types=1);

namespace DB\SQL\Statement;

use DB\SQL\SQLInterface;
use RuntimeException;
use Stringable;

abstract class AbstractStatement implements Stringable
{
    protected const LIST_SEPARATOR = ',';
    protected const EMPTY_STRING = '';
    private array $parameters = [];
    private int $parameterIndex = 0;
    protected null|string $query = null;

    public function __construct(
        protected readonly string $table,
        protected readonly bool   $useTransaction = false
    )
    {
    }

    protected function prepareKey(string $key): string
    {
        if (str_contains($key, '`') ||
            str_contains($key, '.') ||
            str_contains($key, ' ') ||
            str_contains($key, '(')) {
            return $key;
        }
        return sprintf('`%s`', $key);
    }

    protected function prepareValue(bool|string|int|float|null|SQLInterface $value): string
    {
        if ($value instanceof SQLInterface) {
            foreach ($value->getParameters() as $key => $pValue) {
                $this->parameters[$key] = $pValue;
            }
            return $value->__toString();
        }
        return $this->createParameter($value);
    }

    public function createParameter(float|bool|int|string|null $value): string
    {
        $this->parameters[$key = sprintf(':p%d', $this->parameterIndex++)] = $value;
        return $key;
    }

    protected function mergeSubSelect(SelectInterface $select): string
    {
        $select->query = null;
        $select->parameterIndex = $this->parameterIndex;
        $query = rtrim((string)$select, ';');
        foreach ($select->parameters as $key => $value) {
            $this->parameters[$key] = $value;
        }
        $this->parameterIndex = $select->parameterIndex;
        return $query;
    }

    protected function getParameters(): array
    {
        return $this->parameters;
    }

    protected function validateRuntime(): void
    {
        if ($this->query) {
            throw new RuntimeException("The query can no longer be modified!");
        }
    }
}
