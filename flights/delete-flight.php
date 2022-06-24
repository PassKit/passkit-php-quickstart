<?php

require_once "../vendor/passkit/passkit-php-grpc-sdk/lib/extra/google/api/";
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// MODIFY WITH THE VARIABLES NEEDED FOR FLIGHTS 
$carrierCode = "";
// delete-flight takes an existing flight number as well as other details and deletes the flight associated with it.
//If the flight doesn't exist it cannot be deleted.
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

    // Set the flight request body
    $flight = new Flights\FlightRequest();
    $flight->setCarrierCode($carrierCode);
    $flight->setFlightNumber("12345");
    $flight->setBoardingPoint("YY4");
    $flight->setDeplaningPoint("ADP");
    $departureDate = new DateTime();
    $departureDate->setDate(2022, 6, 28);
    $flight->setDepartureDate($departureDate->getTimestamp());
    $departureTime = new Io\Time();
    $departureTime->setHour(13);
    $departureTime->setMinute(00);
    $departureTime->setSecond(00);

    list($id, $status) = $client->deleteFlight($flight)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo $result->getId() . "/n";
} catch (Exception $e) {
    echo $e;
}
