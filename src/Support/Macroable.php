<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Support;

use BadMethodCallException;
use Closure;
use ReflectionClass;
use ReflectionMethod;

/**
 * Trait для добавления поддержки макросов в классы
 *
 * Позволяет динамически расширять функциональность классов во время выполнения.
 */
trait Macroable
{
    /**
     * Зарегистрированные макросы.
     *
     * @var array<string, callable>
     */
    protected static array $macros = [];

    /**
     * Зарегистрировать макрос.
     *
     * @param string $name Название макроса
     * @param callable $macro Callable функция
     * @return void
     */
    public static function macro(string $name, callable $macro): void
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Зарегистрировать несколько макросов.
     *
     * @param array<string, callable> $macros Массив макросов
     * @return void
     */
    public static function mixin(object $mixin, bool $replace = true): void
    {
        $methods = (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            if ($replace || !static::hasMacro($method->name)) {
                $method->setAccessible(true);
                static::macro($method->name, $method->invoke($mixin));
            }
        }
    }

    /**
     * Проверить существование макроса.
     *
     * @param string $name Название макроса
     * @return bool
     */
    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Удалить все макросы.
     *
     * @return void
     */
    public static function flushMacros(): void
    {
        static::$macros = [];
    }

    /**
     * Динамический вызов макроса.
     *
     * @param string $method Название метода
     * @param array $parameters Параметры
     * @return mixed
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        if (!static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo(null, static::class);
        }

        return $macro(...$parameters);
    }

    /**
     * Динамический вызов макроса на экземпляре.
     *
     * @param string $method Название метода
     * @param array $parameters Параметры
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (!static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }
}

