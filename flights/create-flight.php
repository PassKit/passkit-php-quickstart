<?php
require_once dirname(__DIR__) . "/vendor/autoload.php";
PassKit\Quickstart\Env::load(dirname(__DIR__) . "/.env");

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// MODIFY WITH THE VARIABLES NEEDED FOR FLIGHTS 
$templateId = "";
$carrierCode = "YY";
// create-flight takes templateId to use as base template and uses a carrier code and creates a new flight.
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

    // Set the flight body
    $flight = new Flights\Flight();
    $flight->setCarrierCode($carrierCode);
    $flight->setFlightNumber("12345");
    $flight->setBoardingPoint("YY4");
    $flight->setDeplaningPoint("ADP");
    $future = new DateTimeImmutable('+7 days');
    $departureDate = new Io\Date();
    $departureDate->setYear((int) $future->format('Y'));
    $departureDate->setMonth((int) $future->format('n'));
    $departureDate->setDay((int) $future->format('j'));
    $flight->setDepartureDate($departureDate);
    $departureTime = new Io\LocalDateTime();
    $departureTime->setDateTime($future->format('Y-m-d') . 'T13:00:00');
    $flight->setScheduledDepartureTime($departureTime);
    $arrivalTime = new Io\LocalDateTime();
    $arrivalTime->setDateTime($future->format('Y-m-d') . 'T15:00:00');
    $flight->setScheduledArrivalTime($arrivalTime);
    $flight->setPassTemplateId($templateId);

    list($id, $status) = $client->createFlight($flight)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo "Flight created with code: " . $id->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
