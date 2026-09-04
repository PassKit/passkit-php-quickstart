<?php
require_once dirname(__DIR__) . "/vendor/autoload.php";
PassKit\Quickstart\Env::load(dirname(__DIR__) . "/.env");

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// create-program takes a new program name and creates a new program. The method returns the program id.
// A program needs to be created because program functions as a class object for tier and members.
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
    // Generate PassKit Client object for Membership protocol.
    $client = new Members\MembersClient((getenv("PASSKIT_ADDRESS") ?: "grpc.pub1.passkit.io") . ":" . (getenv("PASSKIT_PORT") ?: "443"), [
        'credentials' => $credentials
    ]);
    // Set the program body
    $program = new Members\Program();
    $program->setName("Quickstart Program");
    $program->setStatus([1, 4]);

    list($id, $status) = $client->createProgram($program)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    //You can use the programId displayed below for other membership methods
    echo "ProgramId " . $id->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
