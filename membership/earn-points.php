<?php
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

// MODIFY WITH THE VARIABLES OF YOUR PROGRAM, TIER AND MEMBER
$memberId = "";
$programId = "";
$tierId = "";
// earn-points takes a programId of an existing program and memberId of existing member to add points to chosen member.
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

    // Set the Member points body
    //The points to add to should be whatever point scheme is used on your card e.g. Points, TierPoints or SecondaryPoints
    $memberPointsRequest = new \Members\EarnBurnPointsRequest();
    $memberPointsRequest->setId($memberId);
    $memberPointsRequest->setPoints(2000);
    $memberPointsRequest->setSecondaryPoints(1000);
    $memberPointsRequest->setTierPoints(100);


    list($result, $status) = $client->earnPoints($memberPointsRequest)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo "Member: " . $result->getId() . " has " . $result->getPoints() . " points. \n";
} catch (Exception $e) {
    echo $e;
}
