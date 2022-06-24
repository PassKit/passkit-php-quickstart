<?php

require_once "../vendor/passkit/passkit-php-grpc-sdk/lib/extra/google/api/";
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// create-airport takes a new airport code and creates a new airport.
// If the airport already exists it cannot be created.
// Please make sure an arrival and departure airport exist.
try {
    $ca_filename = "ca-chain.pem";
    $key_filename = "key.pem";
    $cert_filename = "certificate.pem";
    $path = "../certs/";

    $credentials = Grpc\ChannelCredentials::createSsl(
        file_get_contents($path . $ca_filename),
        file_get_contents($path . $key_filename),
        file_get_contents($path . $cert_filename)
    );
    // Generate a flight module client
    $client = new Flights\FlightsClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);

    // Set the departure airport body
    $airport = new Flights\Port();
    $airport->setIataAirportCode("YY4");
    $airport->setIcaoAirportCode("YYYY");
    $airport->setCityName("London");
    $airport->setAirportName("London");
    $airport->setCountryCode("IE");
    $airport->setTimezone("Europe/London");


    list($id, $status) = $client->createPort($airport)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo $result->getId() . "/n";
    // Set the arrival airport body if does not currently exist
    $airport = new Flights\Port();
    $airport->setIataAirportCode("ADP");
    $airport->setIcaoAirportCode("ADPY");
    $airport->setCityName("London");
    $airport->setAirportName("London");
    $airport->setCountryCode("IE");
    $airport->setTimezone("Europe/London");


    list($id, $status) = $client->createPort($airport)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo $result->getId() . "/n";
} catch (Exception $e) {
    echo $e;
}
