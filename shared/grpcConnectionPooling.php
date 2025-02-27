<?php

require '../vendor/autoload.php';

use Grpc\ChannelCredentials;
use Flights\Flight;
use Members\MembersClient;
use Single_use_coupons\SingleUseCouponsClient;
use Event_tickets\EventTicketsClient;
use Flights\FlightsClient;
use Io\TemplatesClient;

class GrpcConnectionPool
{
    private int $poolSize;

    public function __construct(int $poolSize)
    {
        $this->poolSize = $poolSize;
    }

    public function buildSslContext(): object
    {
        $ca_filename = "ca-chain.pem";
        $key_filename = "key.pem";
        $cert_filename = "certificate.pem";
        $path = "../certs/";

        return ChannelCredentials::createSsl(
            file_get_contents($path . $ca_filename),
            file_get_contents($path . $key_filename),
            file_get_contents($path . $cert_filename)
        );
    }
}

function grpcConnectionPooling()
{
    $grpcPool = new GrpcConnectionPool(5);
    $credentials = $grpcPool->buildSslContext();

    try {
        // Generate gRPC clients with the correct credentials
        $membersStub = new MembersClient('grpc.pub1.passkit.io:443', [
            'credentials' => $credentials
        ]);

        $couponsStub = new SingleUseCouponsClient('grpc.pub1.passkit.io:443', [
            'credentials' => $credentials
        ]);

        $eventStub = new EventTicketsClient('grpc.pub1.passkit.io:443', [
            'credentials' => $credentials
        ]);

        $flightStub = new FlightsClient('grpc.pub1.passkit.io:443', [
            'credentials' => $credentials
        ]);

        $templatesStub = new TemplatesClient('grpc.pub1.passkit.io:443', [
            'credentials' => $credentials
        ]);
    } finally {
        // Cleanup
        unset($membersStub, $couponsStub, $eventStub, $flightStub, $templatesStub);
    }
}

grpcConnectionPooling();
