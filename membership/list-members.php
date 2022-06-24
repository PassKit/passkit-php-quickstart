<?php
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

// MODIFY WITH THE VARIABLES OF YOUR PROGRAM AND TIER
$programId = "programId";
// list-members takes search conditions as pagination object and returns list of member records which match with the conditions.
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
 // Generate a members module client
    $client = new Members\MembersClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);

    // Set the Member body
    $listRequest = new Members\ListRequest();
    $listRequest->setProgramId($programId);

    $call= $client->listMembers($listRequest);
    $members = $call->responses();
    foreach ($members as $member) {
        echo $member->getId() . "\n";
    }
} catch (Exception $e) {
    echo $e;
}
