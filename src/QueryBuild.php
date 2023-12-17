<?php

namespace Inilim\QueryBuild;

use Inilim\Array\Array_;
// ------------------------------------------------------------------
// Exception
// ------------------------------------------------------------------
use Inilim\QueryBuild\Exception\EmptyKeyException;

class QueryBuild
{
    protected string $query;
    /**
     * @var mixed[]|array{}
     */
    protected array $query_as_array;
    protected bool $null_as_empty_string = false;
    protected Array_ $array;

    public function __construct(?string $url_or_query = null, ?bool $null_as_empty_string = null)
    {
        $this->array = new Array_;
        if (is_bool($null_as_empty_string)) $this->null_as_empty_string = $null_as_empty_string;

        if (is_null($url_or_query)) $result = '';
        else {
            $result = parse_url($url_or_query);
            $result = $result['query'] ?? $result['path'] ?? '';
        }

        if ($result === '') $this->query = '';
        // исключаем query типа "/path/path/..."
        elseif (str_contains($result, '/')) $this->query = '';
        else $this->query = $result;

        if ($this->query === '') $output = [];
        else parse_str($this->query, $output);

        $this->query_as_array = $output;
    }

    /**
     * @param mixed $value
     * @throws EmptyKeyException
     */
    public function addParam(string|int|float $key, $value): self
    {
        $key = strval($key);
        if ($key === '') throw new EmptyKeyException;
        $this->query_as_array[$key] = $value;
        return $this;
    }

    /**
     * @param mixed $value
     * @throws EmptyKeyException
     */
    public function addParamDot(string $path, $value): self
    {
        if ($path === '') throw new EmptyKeyException;
        $this->array->set($this->query_as_array, $path, $value);
        return $this;
    }

    /**
     * @param array<int|string,mixed> $params
     */
    public function addParams(array $params): self
    {
        foreach ($params as $key => $value) {
            $this->addParam($key, $value);
        }
        return $this;
    }

    /**
     * @param array<string,mixed> $params
     */
    public function addParamsDot(array $params): self
    {
        foreach ($params as $path => $value) {
            $this->addParamDot($path, $value);
        }
        return $this;
    }

    /**
     * @param (string|int|float)[]|string|int|float $keys
     */
    public function removeParams(array|string|int|float $keys): self
    {
        $keys = $this->array->wrap($keys);
        foreach ($keys as $key) {
            $key = strval($key);
            // if ($key === '') throw new EmptyKeyException;
            unset($this->query_as_array[$key]);
        }
        return $this;
    }

    /**
     * @param string[]|string $paths
     */
    public function removeParamsDot(array|string $paths): self
    {
        $paths = $this->array->wrap($paths);
        foreach ($paths as $path) {
            $path = strval($path);
            $this->array->forget($this->query_as_array, $path);
        }
        return $this;
    }

    public function removeAll(): self
    {
        $this->query_as_array = [];
        return $this;
    }

    /**
     * array_key_exists
     */
    public function hasParam(string|int|float $key): bool
    {
        $key = strval($key);
        // if ($key === '') throw new EmptyKeyException;
        return array_key_exists($key, $this->query_as_array);
    }

    public function getParam(string|int|float $key): mixed
    {
        $key = strval($key);
        // if ($key === '') throw new EmptyKeyException;
        return $this->query_as_array[$key] ?? null;
    }

    /**
     * @return mixed[]|array{}
     */
    public function getQueryAsArray(?bool $null_as_empty_string = null): array
    {
        $null_as_empty_string ??= $this->null_as_empty_string;

        if ($null_as_empty_string) return $this->nullToEmptyString($this->query_as_array);

        return $this->query_as_array;
    }

    public function getQuery(?bool $null_as_empty_string = null): string
    {
        $null_as_empty_string ??= $this->null_as_empty_string;

        if ($null_as_empty_string) return http_build_query(
            $this->nullToEmptyString($this->query_as_array)
        );

        return http_build_query($this->query_as_array);
    }

    public function getQueryUrlDecode(?bool $null_as_empty_string = null): string
    {
        return urldecode($this->getQuery($null_as_empty_string));
    }

    /**
     * тип null в виде строки ""
     */
    public function nullAsEmptyString(bool $yes_no): self
    {
        $this->null_as_empty_string = $yes_no;
        return $this;
    }

    /**
     * @param mixed[]|array{} $array
     * @return mixed[]|array{}
     */
    protected function nullToEmptyString(array $array): array
    {
        array_walk_recursive($array, function (&$value) {
            if (is_null($value)) $value = '';
        });

        return $array;
    }
}
