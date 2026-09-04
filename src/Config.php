<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

use InvalidArgumentException;

final readonly class Config
{
    public function __construct(
        public string $address,
        public int $port,
        public string $rootCertificate,
        public string $privateKey,
        public string $certificate,
        public string $connectionMode,
        public int $poolSize,
        public ?string $recipientEmail,
        public ?string $appleCertificate,
        public bool $keepAssets,
        public bool $allowDestructive,
    ) {
    }

    public static function fromEnvironment(string $root): self
    {
        Env::load($root . '/.env');
        $path = static fn (string $name, string $default): string => self::absolute(
            self::value($name, $default),
            $root,
        );

        $config = new self(
            self::value('PASSKIT_ADDRESS', 'grpc.pub1.passkit.io'),
            (int) self::value('PASSKIT_PORT', '443'),
            $path('PASSKIT_ROOT_CERT', './certs/ca-chain.pem'),
            $path('PASSKIT_PRIVATE_KEY', './certs/key.pem'),
            $path('PASSKIT_CERTIFICATE', './certs/certificate.pem'),
            strtolower(self::value('PASSKIT_CONNECTION_MODE', 'pool')),
            (int) self::value('PASSKIT_POOL_SIZE', '5'),
            self::optional('PASSKIT_RECIPIENT_EMAIL'),
            self::optional('PASSKIT_APPLE_CERTIFICATE'),
            self::boolean('PASSKIT_KEEP_ASSETS'),
            self::boolean('PASSKIT_ALLOW_DESTRUCTIVE'),
        );
        $config->validate();

        return $config;
    }

    public function target(): string
    {
        return "{$this->address}:{$this->port}";
    }

    public function passUrl(string $id): string
    {
        $region = str_contains($this->address, 'pub2') ? 'pub2' : 'pub1';
        return "https://{$region}.pskt.io/{$id}";
    }

    public function validate(bool $requireFlightCertificate = false): void
    {
        if (!in_array($this->connectionMode, ['single', 'pool'], true)) {
            throw new InvalidArgumentException('PASSKIT_CONNECTION_MODE must be single or pool.');
        }
        if ($this->port < 1 || $this->port > 65535 || $this->poolSize < 1) {
            throw new InvalidArgumentException('PASSKIT_PORT and PASSKIT_POOL_SIZE must be positive.');
        }
        foreach ([$this->rootCertificate, $this->privateKey, $this->certificate] as $file) {
            if (!is_readable($file)) {
                throw new InvalidArgumentException("Credential file is missing or unreadable: {$file}");
            }
        }
        if ($requireFlightCertificate && $this->appleCertificate === null) {
            throw new InvalidArgumentException('Flights require PASSKIT_APPLE_CERTIFICATE in .env.');
        }
    }

    private static function value(string $name, string $default): string
    {
        $value = getenv($name);
        return $value === false || trim($value) === '' ? $default : trim($value);
    }

    private static function optional(string $name): ?string
    {
        $value = getenv($name);
        return $value === false || trim($value) === '' ? null : trim($value);
    }

    private static function boolean(string $name): bool
    {
        return filter_var(self::value($name, 'false'), FILTER_VALIDATE_BOOL);
    }

    private static function absolute(string $path, string $root): string
    {
        return str_starts_with($path, '/') ? $path : $root . '/' . ltrim($path, './');
    }
}
