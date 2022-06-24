<?php

require_once "../vendor/passkit/passkit-php-grpc-sdk/lib/extra/google/api/";
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// MODIFY WITH THE VARIABLES NEEDED FOR FLIGHTS 
$carrierCode = "";
// delete-flight-designator takes an existing flight designation and deletes the flight designator associated with it.
//If the flight designator doesn't exist it cannot be deleted.
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

    // Set the flight designator request body
    $flightDesignator = new Flights\FlightDesignatorRequest();
    $flightDesignator->setCarrierCode($carrierCode);
    $flightDesignator->setFlightNumber("12345");
    $flightDesignator->setRevision(0);

    list($id, $status) = $client->deleteFlightDesignator($flightDesignator)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo $result->getId() . "/n";
} catch (Exception $e) {
    echo $e;
}
