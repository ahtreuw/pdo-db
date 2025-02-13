<?php declare(strict_types=1);

namespace DB\SQL;

use DateInterval;
use PDO;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException as CacheItemPoolInvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInvalidArgumentException;
use ReflectionClass;
use ReflectionException;

trait FetchDataTrait
{
    private const DB_SELECT_CACHE_ID_PREFIX = 'db.select.';
    private const  STD_CLASS = 'stdClass';

    public function fetchAll(
        int                        $mode = PDO::FETCH_DEFAULT,
        null|int|DateInterval|bool $cacheTtl = false,
        mixed                      ...$args
    ): array
    {
        if ($cacheTtl === false || is_null($this->cacheAdapter)) {
            return $this->exec()->fetchAll($mode, ...$args) ?: [];
        }

        if ($result = $this->getCachedValue($cacheId = $this->getCacheId())) {
            return $result;
        }

        $result = $this->fetchAll($mode, false, ...$args);

        $this->cacheResult($cacheId, $cacheTtl, $result);

        return $result;
    }

    public function fetchAllClass(
        string                     $class,
        int                        $mode = PDO::FETCH_DEFAULT,
        null|int|DateInterval|bool $cacheTtl = false,
        mixed                      ...$args
    ): array
    {
        $result = $this->fetchAll($mode, $cacheTtl, ...$args);

        if ($mode & PDO::FETCH_GROUP) {
            foreach ($result as $i => $subResult) {
                foreach ($subResult as $j => $item) {
                    $result[$i][$j] = $this->mergeInto($class, $item);
                }
            }
        } else {
            foreach ($result as $i => $item) {
                $result[$i] = $this->mergeInto($class, $item);
            }
        }
        return $result;
    }

    public function fetch(
        int                        $mode = PDO::FETCH_DEFAULT,
        null|int|DateInterval|bool $cacheTtl = false,
        int                        $cursorOrientation = PDO::FETCH_ORI_NEXT,
        int                        $cursorOffset = 0
    ): mixed
    {
        if ($cacheTtl === false || is_null($this->cacheAdapter)) {
            return $this->exec()->fetch($mode, $cursorOrientation, $cursorOffset);
        }

        if ($result = $this->getCachedValue($cacheId = $this->getCacheId())) {
            return $result;
        }

        $result = $this->fetch($mode, false, $cursorOrientation, $cursorOffset);

        $this->cacheResult($cacheId, $cacheTtl, $result);

        return $result;
    }

    public function fetchObject(
        string                     $class = 'stdClass',
        array                      $constructorArgs = [],
        null|int|DateInterval|bool $cacheTtl = false
    ): null|object
    {
        if ($cacheTtl === false || is_null($this->cacheAdapter)) {
            return $this->exec()->fetchObject($class, $constructorArgs) ?: null;
        }

        if ($result = $this->getCachedValue($cacheId = $this->getCacheId())) {
            return $result;
        }

        $result = $this->fetchObject($class, $constructorArgs);

        $this->cacheResult($cacheId, $cacheTtl, $result);

        return $result;
    }

    public function fetchClass(
        string                     $class,
        array                      $constructorArgs = [],
        null|int|DateInterval|bool $cacheTtl = false
    ): null|object
    {
        $result = $this->fetchObject(self::STD_CLASS, $constructorArgs, $cacheTtl);
        return $result ? $this->mergeInto($class, $result) : null;
    }

    public function fetchColumn(
        int                        $column = 0,
        null|int|DateInterval|bool $cacheTtl = false
    ): mixed
    {
        if ($cacheTtl === false || is_null($this->cacheAdapter)) {
            return $this->exec()->fetchColumn($column) ?: null;
        }

        if ($result = $this->getCachedValue($cacheId = $this->getCacheId())) {
            return $result;
        }

        $result = $this->fetchColumn($column);

        $this->cacheResult($cacheId, $cacheTtl, $result);

        return $result;
    }

    /**
     * @return string
     */
    private function getCacheId(): string
    {
        return self::DB_SELECT_CACHE_ID_PREFIX . md5($this->__toString() . json_encode($this->getParameters()));
    }

    /**
     * @throws CacheItemPoolInvalidArgumentException
     * @throws SimpleCacheInvalidArgumentException
     */
    private function getCachedValue(string $cacheId): mixed
    {
        if (
            $this->cacheAdapter instanceof CacheInterface &&
            $this->cacheAdapter->has($cacheId)
        ) {
            return $this->cacheAdapter->get($cacheId);
        }
        if (
            $this->cacheAdapter instanceof CacheItemPoolInterface &&
            $this->cacheAdapter->hasItem($cacheId)
        ) {
            return $this->cacheAdapter->getItem($cacheId)->get();
        }
        return null;
    }

    /**
     * @throws SimpleCacheInvalidArgumentException
     * @throws CacheItemPoolInvalidArgumentException
     */
    private function cacheResult(string $cacheId, null|int|DateInterval $cacheTtl, mixed $result): void
    {
        if ($this->cacheAdapter instanceof CacheInterface) {
            $this->cacheAdapter->set($cacheId, $result, $cacheTtl);
            return;
        }
        if ($this->cacheAdapter instanceof CacheItemPoolInterface) {
            $this->cacheAdapter->save($this->cacheAdapter->getItem($cacheId)->set($result)->expiresAfter($cacheTtl));
        }
    }

    /**
     * @throws ReflectionException
     */
    protected function mergeInto(string|object $class, object|array $data): object
    {
        $reflector = new ReflectionClass($class);
        if (is_object($class) === false) {
            $class = $reflector->newInstanceWithoutConstructor();
        }
        foreach ($data as $key => $value) {
            if ($reflector->hasProperty($property = $map[$key] ?? $key)) {
                $reflector->getProperty($property)->setValue($class, $value);
            }
        }
        return $class;
    }
}
