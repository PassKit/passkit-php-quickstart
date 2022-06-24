<?php

require_once "../vendor/passkit/passkit-php-grpc-sdk/lib/extra/google/api/";
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// MODIFY WITH THE VARIABLES NEEDED FOR FLIGHTS 
$carrierCode = "";
$emailAddress = ""; // change to your email address
// create-boarding-pass takes carrierCode and customer details creates a new boarding pass, and sends a welcome email to deliver boarding pass url.
// The method returns the boarding pass id. Boarding Pass id is a part of card url.
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

    // Set the boarding pass body
    $boardingPass = new Flights\BoardingPassRecord();
    $boardingPass->setCarrierCode($carrierCode);
    $boardingPass->setBoardingPoint("YYY");
    $boardingPass->setDeplaningPoint("LHR");
    $boardingPass->setOperatingCarrierPNR("");
    $boardingPass->setFlightNumber("12345");
    $boardingPass->setSequenceNumber(2);
    $passenger = new Flights\Passenger();
    $passengerDetails = new Io\Person();
    $passengerDetails->setSurname("Smith");
    $passengerDetails->setForename("Bailey");
    $passengerDetails->setDisplayName("Bailey");
    $passengerDetails->setEmailAddress($emailAddress);
    $passenger->setPassengerDetails($passengerDetails);
    $boardingPass->setPassenger($passenger);
    $departureDate = new Io\Date();
    $departureDate->setDay(28);
    $departureDate->setMonth(7);
    $departureDate->setYear(2022);
    $boardingPass->setDepartureDate($departureDate);

    list($id, $status) = $client->createBoardingPass($boardingPass)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo "https://pub1.pskt.io/" . $id->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
