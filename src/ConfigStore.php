<?php

declare(strict_types=1);

namespace Lookout\Cli;

/**
 * Persists API base URL and token under ~/.lookout/config.json.
 */
final class ConfigStore
{
    private const DIR = '.lookout';

    private const FILE = 'config.json';

    public function __construct(
        private readonly string $homeDir,
    ) {}

    public static function default(): self
    {
        $home = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? '';
        if ($home === '') {
            throw new \RuntimeException('Could not determine home directory; set HOME or USERPROFILE.');
        }

        return new self($home);
    }

    public function path(): string
    {
        return $this->homeDir.DIRECTORY_SEPARATOR.self::DIR.DIRECTORY_SEPARATOR.self::FILE;
    }

    /**
     * @return array{base_url: string, api_token: string}|null
     */
    public function read(): ?array
    {
        $path = $this->path();
        if (! is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return null;
        }
        $base = isset($data['base_url']) && is_string($data['base_url']) ? trim($data['base_url']) : '';
        $token = isset($data['api_token']) && is_string($data['api_token']) ? trim($data['api_token']) : '';
        if ($base === '' || $token === '') {
            return null;
        }

        return ['base_url' => rtrim($base, '/'), 'api_token' => $token];
    }

    /**
     * @param  array{base_url: string, api_token: string}  $config
     */
    public function write(array $config): void
    {
        $dir = $this->homeDir.DIRECTORY_SEPARATOR.self::DIR;
        if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new \RuntimeException("Could not create directory: {$dir}");
        }
        $path = $this->path();
        $payload = json_encode([
            'base_url' => rtrim($config['base_url'], '/'),
            'api_token' => $config['api_token'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new \RuntimeException('Could not encode config JSON.');
        }
        if (file_put_contents($path, $payload."\n", LOCK_EX) === false) {
            throw new \RuntimeException("Could not write config: {$path}");
        }
        @chmod($path, 0600);
    }

    public function clear(): void
    {
        $path = $this->path();
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
