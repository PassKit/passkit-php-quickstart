<?php
require_once dirname(__DIR__) . "/vendor/autoload.php";
PassKit\Quickstart\Env::load(dirname(__DIR__) . "/.env");

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

// MODIFY WITH THE VARIABLES OF YOUR PROGRAM, TIER AND EMAIL
$programId = "";
$tierId = "base";
$email = "";
// enrol-member takes programId, tierId and memberDetails, creates a new member record, and sends a welcome email to deliver membership card url.
// The method returns the member id. Member id is a part of card url.
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
    // Generate a members module client
    $client = new Members\MembersClient((getenv("PASSKIT_ADDRESS") ?: "grpc.pub1.passkit.io") . ":" . (getenv("PASSKIT_PORT") ?: "443"), [
        'credentials' => $credentials
    ]);

    // Set the Member body
    $member = new Members\Member();
    $member->setProgramId($programId);
    $member->setTierId($tierId);

    $person = new Io\Person();
    $person->setDisplayName("Peter Pan");
    $dateOfBirth = new Io\Date();
    $dateOfBirth->setDay(22);
    $dateOfBirth->setMonth(6);
    $dateOfBirth->setYear(2020);
    $person->setDateOfBirth($dateOfBirth);
    $person->setEmailAddress($email);
    $member->setPerson($person);

    list($id, $status) = $client->enrolMember($member)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }
    //You can use the memberId displayed below for other membership methods
    echo "Pass URL: " . "https://pub1.pskt.io/" . $id->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
