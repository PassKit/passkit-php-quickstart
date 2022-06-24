<?php


require_once "../vendor/passkit/passkit-php-grpc-sdk/lib/extra/google/api/";
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

// MODIFY WITH THE VARIABLES OF YOUR CAMPAIGN AND OFFER
$campaignId = "05faUsEvatLifOwxKWmS0Q";
$offerId = "base";
// count-coupons takes search conditions as pagination object and returns the number of coupons who match with the condition.
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
    // Generate a coupons module client
    $client = new  Single_use_coupons\SingleUseCouponsClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);
    // Set the coupon list request body
    $listRequest = new Single_use_coupons\ListRequest();
    $listRequest->setCouponCampaignId($campaignId);
    $filter = new Io\Filter();
    $filter->setFilterField("offerId");
    $filter->setFilterValue($offerId);
    $filter->setFilterOperator("eq");
    $listRequest->setFilters($filter);


    list($id, $status) = $client->countCouponsByCouponCampaign($listRequest)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo $result->getId() . "/n";
} catch (Exception $e) {
    echo $e;
}
