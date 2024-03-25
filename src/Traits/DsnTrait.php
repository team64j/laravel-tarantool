<?php

declare(strict_types=1);

namespace Team64j\LaravelTarantool\Traits;

trait DsnTrait
{
    /**
     * Create a DSN string from a configuration.
     *
     * @param array $config
     *
     * @return string
     */
    protected function getDsn(array $config): string
    {
        return $this->hasDsnString($config)
            ? $this->getDsnString($config)
            : $this->getHostDsn($config);
    }

    /**
     * Determine if the given configuration array has a dsn string.
     *
     * @param array $config
     *
     * @return bool
     */
    protected function hasDsnString(array $config): bool
    {
        return !empty($config['dsn']);
    }

    /**
     * Get the DSN string form configuration.
     *
     * @param array $config
     *
     * @return string
     */
    protected function getDsnString(array $config): string
    {
        return $config['dsn'];
    }

    /**
     * Get the DSN string for a host / port configuration.
     *
     * @param array $config
     *
     * @return string
     */
    protected function getHostDsn(array $config): string
    {
        $host = $config['host'];

        if (!empty($config['port']) && !str_contains($host, ':')) {
            $host = $host . ':' . $config['port'];
        }

        $auth = $config['username'] . ':' . $config['password'];

        $options = !empty($config['options']) ? http_build_query($config['options'], '', '&') : null;

        $connType = !empty($config['type']) ? $config['type'] : 'tcp';

        return $connType . '://' . $auth . '@' . $host . ($options ? '/?' . $options : '');
    }
}
