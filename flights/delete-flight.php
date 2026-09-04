<?php
require_once dirname(__DIR__) . "/vendor/autoload.php";
PassKit\Quickstart\Env::load(dirname(__DIR__) . "/.env");

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// MODIFY WITH THE VARIABLES NEEDED FOR FLIGHTS 
$carrierCode = "YY";
// delete-flight takes an existing flight number as well as other details and deletes the flight associated with it.
//If the flight doesn't exist it cannot be deleted.
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

    // Set the flight request body
    $flight = new Flights\FlightRequest();
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

    list($id, $status) = $client->deleteFlight($flight)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo "Flight deleted. \n";
} catch (Exception $e) {
    echo $e;
}
