<?php
require_once "../vendor/autoload.php";


putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// create-template creates the pass template for flights and boarding passes.
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

    //Create templates client
    $templatesclient = new Io\TemplatesClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);

    // Create the template for the card
    // In order to create a ticket type, we need a pass template id which holds pass design data. Let's use the default pass template for now.
    $defaultTemplateRequest = new Io\DefaultTemplateRequest();
    $defaultTemplateRequest->setProtocol(102);
    $defaultTemplateRequest->setRevision(1);
    $defaultPassTemplate = new Io\PassTemplate();

    $defaultPassTemplate->$templatesclient->getDefaultTemplate($defaultTemplateRequest)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    // If you use the default template, you need to set name, description and timezone because these fields are mandatory.
    $defaultPassTemplate->setName("Quickstart Event Ticket");
    $defaultPassTemplate->setDescription("quick start event ticket");
    $defaultPassTemplate->setTimezone("Europe/London");


    list($id, $status) = $templatesclient->createTemplate($defaultPassTemplate)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    //You can use the templateId displayed below for other event ticket methods
    echo "TemplateId: " . $id->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
