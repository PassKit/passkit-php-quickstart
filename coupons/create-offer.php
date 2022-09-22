<?php

require_once "../vendor/passkit/passkit-php-grpc-sdk/lib/extra/google/api/";
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// create-offer takes a campaignId of an existing campaign, creates a new template (based of default template), creates an offer, and links this offer to the campaign.
// The method returns the offer id.
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
    // Generate a template module client
    $client = new  Single_use_coupons\SingleUseCouponsClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);

    //Create templates client
    $templatesclient = new Io\TemplatesClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);

    // Create the template for the card
    // In order to create a tier, we need a pass template id which holds pass design data. Let's use the default pass template for now.
    $defaultTemplateRequest = new Io\DefaultTemplateRequest();
    $defaultTemplateRequest->setProtocol(101);
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


    // Set the offer body
    $offer = new Single_use_coupons\CouponOffer();
    $offer->setId("base");
    $offer->setBeforeRedeemPassTemplateId($defaultPassTemplate->getId());
    $offer->setOfferTitle("BaseOffer");
    $offer->setOfferShortTitle("BaseOffer");
    $offer->setOfferDetails("Base offer");
    $startdate = new DateTime();
    $enddate = new DateTime();
    $enddate->setDate(2022, 6, 28);
    $offer->setIssueStartDate($startdate->getTimestamp());
    $offer->setIssueEndDate($enddate->getTimestamp());

    list($id, $status) = $client->createCouponOffer($offer)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }
    //You can use the offerId displayed below for other coupon methods
    echo "OfferId" . $offer->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}