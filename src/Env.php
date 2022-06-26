<?php

namespace Reagordi\Component\Env;

use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Repository\RepositoryBuilder;
use PhpOption\Option;
use Closure;

class Env
{
    /**
     * Indicates if the put-env adapter is enabled.
     *
     * @var bool
     */
    protected static bool $putEnv = true;

    /**
     * The environment repository instance.
     *
     * @var RepositoryInterface|null
     */
    protected static ?RepositoryInterface $repository;

    /**
     * Enable the put-env adapter.
     *
     * @return void
     */
    public static function enablePutEnv(): void
    {
        static::$putEnv = true;
        static::$repository = null;
    }

    /**
     * Disable the put-env adapter.
     *
     * @return void
     */
    public static function disablePutEnv(): void
    {
        static::$putEnv = false;
        static::$repository = null;
    }

    /**
     * Get the environment repository instance.
     *
     * @return RepositoryInterface|null
     */
    public static function getRepository(): ?RepositoryInterface
    {
        if (static::$repository === null) {
            $builder = RepositoryBuilder::createWithDefaultAdapters();

            if (static::$putEnv) {
                $builder = $builder->addAdapter(PutenvAdapter::class);
            }

            static::$repository = $builder->immutable()->make();
        }

        return static::$repository;
    }

    /**
     * Gets the value of an environment variable.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Option::fromValue(static::getRepository()->get($key))
            ->map(function ($value) {
                switch (strtolower($value)) {
                    case 'true':
                    case '(true)':
                        return true;
                    case 'false':
                    case '(false)':
                        return false;
                    case 'empty':
                    case '(empty)':
                        return '';
                    case 'null':
                    case '(null)':
                        return null;
                }

                if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
                    return $matches[2];
                }

                return $value;
            })
            ->getOrCall(function () use ($default) {
                return self::value($default);
            });
    }

    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    private static function value(mixed $value, ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}
