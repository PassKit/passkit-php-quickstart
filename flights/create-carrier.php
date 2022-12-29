<?php
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// MODIFY WITH THE VARIABLES NEEDED FOR FLIGHTS 
$appleCertificate = ""; // change to your apple certificate
// create-carrier takes a new carrier code and creates a new carrier.
// If the carrier already exists it cannot be created.
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

    // Set the carrier body
    $carrier = new Flights\Carrier();
    $carrier->setIataCarrierCode("YY");
    $carrier->setAirlineName("PassKit Air");
    $carrier->setPassTypeIdentifier($appleCertificate);

    list($id, $status) = $client->createCarrier($carrier)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo "Carrier created with code: " . $id->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
