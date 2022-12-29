<?php
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// create-campaign takes a new campaign name and creates a new campaign. The method returns the campaign id.
// A campaign needs to be created because campaign functions as a class object for offer and coupons.
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
    // Generate PassKit Client object for Coupon protocol.
    $client = new  Single_use_coupons\SingleUseCouponsClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);
    // Set the campaign body
    $campaign = new Single_use_coupons\CouponCampaign();
    $campaign->setName("Quickstart Campaign");
    $campaign->setStatus([1, 4]);

    list($id, $status) = $client->createCouponCampaign($campaign)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }
    //You can use the campaignId displayed below for other coupon methods
    echo "CampaignId " . $id->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
