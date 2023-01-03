<?php
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// MODIFY WITH THE VARIABLES NEEDED FOR FLIGHTS 
$carrierCode = "";
// delete-carrier takes an existing carrier code and deletes the carrier associated with it.
//If the carrier doesn't exist it cannot be deleted.
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

    // Set the carrier code body
    $carrier = new Flights\CarrierCode();
    $carrier->setCarrierCode($carrier);

    list($id, $status) = $client->deleteCarrier($carrier)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo "Carrier deleted. \n";
} catch (Exception $e) {
    echo $e;
}
