<?php
require_once dirname(__DIR__) . "/vendor/autoload.php";
PassKit\Quickstart\Env::load(dirname(__DIR__) . "/.env");

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

// MODIFY WITH THE VARIABLES OF YOUR CAMPAIGN AND OFFER
$campaignId = "";
$offerId = "";
// count-coupons takes search conditions as pagination object and returns the number of coupons who match with the condition.
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
    // Generate a coupons module client
    $client = new  Single_use_coupons\SingleUseCouponsClient((getenv("PASSKIT_ADDRESS") ?: "grpc.pub1.passkit.io") . ":" . (getenv("PASSKIT_PORT") ?: "443"), [
        'credentials' => $credentials
    ]);
    // Set the coupon list request body
    $listRequest = new Single_use_coupons\ListRequest();
    $listRequest->setCouponCampaignId($campaignId);
    $filter = new Io\FieldFilter();
    $filter->setFilterField("offerId");
    $filter->setFilterValue($offerId);
    $filter->setFilterOperator("eq");
    $filterGroup = new Io\FilterGroup();
    $filterGroup->setFieldFilters([$filter]);
    $filters = new Io\Filters();
    $filters->setFilterGroups([$filterGroup]);
    $listRequest->setFilters($filters);


    list($result, $status) = $client->countCouponsByCouponCampaign($listRequest)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }
    echo  "Number of coupons " . $result->getTotal() . "\n";
} catch (Exception $e) {
    echo $e;
}
