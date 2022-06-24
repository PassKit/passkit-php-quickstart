<?php

require_once "../vendor/autoload.php";


putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// MODIFY WITH THE VARIABLES OF YOUR PROGRAM 
$programId = "05faUsEvatLifOwxKWmS0Q";
// create-tier takes a programId of an existing program, creates a new template (based of default template), creates a tier, and links this tier to the program.
// The method returns the tier id.
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

    //Create templates client
    $templatesclient = new Io\TemplatesClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);

    // Create the template for the card
    // In order to create a tier, we need a pass template id which holds pass design data. Let's use the default pass template for now.
    $defaultTemplateRequest = new Io\DefaultTemplateRequest();
    $defaultTemplateRequest->setProtocol(100);
    $defaultTemplateRequest->setRevision(1);
    $defaultPassTemplate = new Io\PassTemplate();

    $defaultPassTemplate->$templatesclient->getDefaultTemplate($defaultTemplateRequest)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    // If you use the default template, you need to set name, description and timezone because these fields are mandatory.
    $defaultPassTemplate->setName("Quickstart");
    $defaultPassTemplate->setDescription("quick start sample template");
    $defaultPassTemplate->setTimezone("America/New_York");


    list($id, $status) = $templatesclient->createTemplate($defaultPassTemplate)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }


    // Set the tier body
    $tier = new Members\Tier();
    $tier->setName("Base  Tier");
    $tier->setPassTemplateId($defaultPassTemplate->getId());
    $tier->setProgramId($programId);
    $tier->setTierIndex(1);
    $tier->setTimezone("UTC");


    list($id, $status) = $client->createTier($tier)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }
    //You can use the tierId displayed below for other membership methods
    echo "TierId" . $tier->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
