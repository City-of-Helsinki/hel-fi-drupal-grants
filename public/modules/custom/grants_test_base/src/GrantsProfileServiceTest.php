<?php

namespace Drupal\grants_test_base;

use Drupal\grants_profile\GrantsProfileService;

/**
 * Override GrantsProfileService for tests.
 *
 * This add possibility to inject data
 * for unregistered community test case.
 */
class GrantsProfileServiceTest extends GrantsProfileService {

  /**
   * {@inheritdoc}
   */
  public function getGrantsProfileContent(mixed $business, bool $refetch = FALSE): array {
    return [
      "companyName" => "Pöpilä",
      "officials" => [
            [
              "official_id" => "7f534790-5f8d-4a8c-aa40-6950712d9bbd",
              "name" => "Nordea Demo",
              "email" => "mailfromprofile@example.com",
              "phone" => "+35812121212121212121",
              "role" => "11",
            ],
      ],
      "addresses" => [
            [
              "address_id" => "ad1ecd92-349b-4cfa-82e4-5f134631daac",
              "street" => "Kaukotie 5",
              "postCode" => "01300",
              "city" => "Helsinki",
              "country" => NULL,
            ],
      ],
      "bankAccounts" => [
            [
              "bankAccount" => "FI2523629411259741",
              "ownerName" => "Wii WIi",
              "ownerSsn" => "290492-932R",
              "confirmationFile" => "truck_clipart_15144.jpg",
              "bank_account_id" => "d1c2ea21-1c83-4dba-80a9-21d2c1c00881",
            ],
      ],
      "businessId" => "cb9381e7-eede-4868-a089-79ce0c68c37a",
    ];
  }

}
