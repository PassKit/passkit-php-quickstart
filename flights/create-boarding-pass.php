<?php
require_once dirname(__DIR__) . "/vendor/autoload.php";
PassKit\Quickstart\Env::load(dirname(__DIR__) . "/.env");

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// MODIFY WITH THE VARIABLES NEEDED FOR FLIGHTS 
$carrierCode = "YY";
$emailAddress = getenv("PASSKIT_RECIPIENT_EMAIL") ?: "flight.passenger@dummy.passkit.com";
// create-boarding-pass takes carrierCode and customer details creates a new boarding pass, and sends a welcome email to deliver boarding pass url.
// The method returns the boarding pass id. Boarding Pass id is a part of card url.
try {
    $ca_filename = "ca-chain.pem";
    $key_filename = "key.pem";
    $cert_filename = "certificate.pem";
    $path = dirname(__DIR__) . "/certs/";

    $credentials = Grpc\ChannelCredentials::createSsl(
        file_get_contents($path . $ca_filename),
        file_get_contents($path . $key_filename),
        file_get_contents($path . $cert_filename)
    );
    // Generate a flight module client
    $client = new Flights\FlightsClient((getenv("PASSKIT_ADDRESS") ?: "grpc.pub1.passkit.io") . ":" . (getenv("PASSKIT_PORT") ?: "443"), [
        'credentials' => $credentials
    ]);

    // Set the boarding pass body
    $boardingPass = new Flights\BoardingPassRecord();
    $boardingPass->setCarrierCode($carrierCode);
    $boardingPass->setBoardingPoint("YY4");
    $boardingPass->setDeplaningPoint("ADP");
    $boardingPass->setOperatingCarrierPNR("PHP123");
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
    $future = new DateTimeImmutable('+7 days');
    $departureDate = new Io\Date();
    $departureDate->setYear((int) $future->format('Y'));
    $departureDate->setMonth((int) $future->format('n'));
    $departureDate->setDay((int) $future->format('j'));
    $boardingPass->setDepartureDate($departureDate);

    list($response, $status) = $client->createBoardingPass($boardingPass)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    foreach ($response->getBoardingPasses() as $pass) {
        echo ($pass->getUrl() ?: $pass->getGooglePayURL()) . "\n";
    }
} catch (Exception $e) {
    echo $e;
}
