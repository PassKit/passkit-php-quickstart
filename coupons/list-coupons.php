<?php
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

// MODIFY WITH THE VARIABLES OF YOUR CAMPAIGN, OFFER AND COUPON
$campaignId = "";

// list-coupons takes search conditions as pagination object and returns list of coupon records which match with the conditions.
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
    // Set the coupon list request
    $listRequest = new Single_use_coupons\ListRequest();
    $listRequest->setCouponCampaignId($campaignId);

    $call = $client->listCouponsByCouponCampaign($listRequest);
    $coupons = $call->responses();
    foreach ($coupons as $coupon) {
        echo $coupon->getId() . "\n";
    }

    echo "/n";
} catch (Exception $e) {
    echo $e;
}
