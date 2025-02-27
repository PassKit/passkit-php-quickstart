<?php
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

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

    // Create templates client
    $templatesClient = new Io\TemplatesClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);

    // Create the request for the default template
    $defaultTemplateRequest = new Io\DefaultTemplateRequest();
    $defaultTemplateRequest->setProtocol(102);
    $defaultTemplateRequest->setRevision(1);

    // Call getDefaultTemplate and capture the response and status
    list($defaultPassTemplate, $status) = $templatesClient->getDefaultTemplate($defaultTemplateRequest)->wait();

    // Check for errors
    if ($status->code !== Grpc\STATUS_OK) {
        throw new Exception(sprintf('Status Code: %s, Details: %s', $status->code, $status->details));
    }

    // Modify the template fields
    $defaultPassTemplate->setName("Quickstart Event Ticket");
    $defaultPassTemplate->setDescription("Quick start event ticket");
    $defaultPassTemplate->setTimezone("Europe/London");

    // Call createTemplate and capture response and status
    list($templateResponse, $status) = $templatesClient->createTemplate($defaultPassTemplate)->wait();

    // Check for errors
    if ($status->code !== Grpc\STATUS_OK) {
        throw new Exception(sprintf('Status Code: %s, Details: %s', $status->code, $status->details));
    }

    // Print the template ID
    echo "TemplateId: " . $templateResponse->getId() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
