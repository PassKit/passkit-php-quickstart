<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

use Analytics\AnalyticsClient;
use Event_tickets\EventTicketsClient;
use Flights\FlightsClient;
use Grpc\ChannelCredentials;
use Io\CertificatesClient;
use Io\DistributionClient;
use Io\ImagesClient;
use Io\IntegrationsClient;
use Io\MessagesClient;
use Io\TemplatesClient;
use Io\UsersClient;
use Members\MembersClient;
use Raw\RawClient;
use Single_use_coupons\SingleUseCouponsClient;

final class ClientFactory
{
    /** @return array<string, object> */
    public static function create(Config $config): array
    {
        if (!extension_loaded('grpc')) {
            throw new \RuntimeException('The PHP gRPC extension is not enabled. Run `php -m | grep grpc`.');
        }
        putenv('GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA');
        $credentials = ChannelCredentials::createSsl(
            self::read($config->rootCertificate),
            self::read($config->privateKey),
            self::read($config->certificate),
        );
        $options = ['credentials' => $credentials];
        $target = $config->target();

        return [
            'loyalty' => new MembersClient($target, $options),
            'coupons' => new SingleUseCouponsClient($target, $options),
            'eventTickets' => new EventTicketsClient($target, $options),
            'flights' => new FlightsClient($target, $options),
            'templates' => new TemplatesClient($target, $options),
            'images' => new ImagesClient($target, $options),
            'analytics' => new AnalyticsClient($target, $options),
            'distribution' => new DistributionClient($target, $options),
            'messages' => new MessagesClient($target, $options),
            'integrations' => new IntegrationsClient($target, $options),
            'scanners' => new UsersClient($target, $options),
            'certificates' => new CertificatesClient($target, $options),
            'raw' => new RawClient($target, $options),
        ];
    }

    private static function read(string $file): string
    {
        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read {$file}");
        }
        return $contents;
    }
}
