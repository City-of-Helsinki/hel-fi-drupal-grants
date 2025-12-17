<?php

declare(strict_types=1);

namespace Drupal\grants_application\Form;


use Drupal\grants_application\Helper;
use Drupal\grants_application\Mapper\JsonMapper;
use Drupal\grants_application\User\GrantsProfile;
use Drupal\grants_application\User\UserInformationService;
use Drupal\helfi_atv\AtvDocument;

final class JsonMapperService {

  private JsonMapper $mapper;
  private array $dataSources;

  public function __construct(
    private UserInformationService $userInformationService,
    private FormSettingsService $formSettingsService,
  ) {
  }

  /**
   * Map all normal fields, files are excluded.
   *
   * @param string $formTypeId
   * @param string $applicationNumber
   * @param array $form_data
   * @return array
   */
  public function handleMapping(
    string $formTypeId,
    string $applicationNumber,
    array $formData
  ): array {
    $mappingFileName = "ID$formTypeId.json";
    $mapping = json_decode(file_get_contents(__DIR__ . '/Mappings/' . $mappingFileName), TRUE);

    // @todo Fix.
    $mapper = new JsonMapper($mapping);
    $this->mapper = $mapper;

    // @todo Fix.
    $dataSources = $this->getDataSources($formData, $applicationNumber, $formTypeId);
    $this->dataSources = $dataSources;

    return $mapper->map($dataSources);
  }

  public function handleFileMapping(array $bankFile): array {
    $formData = $this->dataSources['form_data'];
    $mappedBankFile = $this->mapper->mapBankFile($this->getSelectedBankAccount($formData), $bankFile);
    $mappedFileData = $this->mapper->mapFiles($this->dataSources);

    $mappedFileData['attachmentsInfo']['attachmentsArray'][] = $mappedBankFile;

    return $mappedFileData;
  }

  /**
   * Get the selected bank account file from grants profile.
   *
   * @param array $formData
   *   The form data.
   * @param AtvDocument $document
   *   The ATV-document.
   *
   * @return array|bool
   *   Bool if this has been done already, otherwise return file data array.
   */
  public function getSelectedBankFile(array $formData, AtvDocument $document): array {
    $selectedBankAccountNumber = $this->getSelectedBankAccount($formData);
    $grantsProfile = $this->userInformationService->getGrantsProfileContent();
    $bankAccounts = $grantsProfile->getBankAccounts();

    $profileFiles = $this->userInformationService->getGrantsProfileAttachments();
    try {
      $bankConfirmationFileArray = Helper::findMatchingBankConfirmationFile(
        $selectedBankAccountNumber,
        $bankAccounts,
        $profileFiles,
      );
    }
    catch (\Exception $e) {
      // User has removed bank account from the profile.
      throw $e;
    }

    return $bankConfirmationFileArray;
  }

  /**
   * Check if one of the grant profile's files is set to the document.
   *
   * @param AtvDocument $document
   *   The document.
   * @return bool
   *   The bank file has been added to the document.
   */
  public function documentBankFileIsSet(AtvDocument $document): bool {
    $grantsProfile = $this->userInformationService->getGrantsProfileContent();
    $bankAccounts = $grantsProfile->getBankAccounts();

    if ($documentAttachments = $document->getAttachments()) {
      foreach ($bankAccounts as $bankAccount) {
        $bankFile = array_find($documentAttachments, fn(array $attachment) => $bankAccount['confirmationFile'] === $attachment['filename']);
        if ($bankFile) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }


  /**
   * Do something related to bank file.
   *
   * @param array $form_data
   * @param AtvDocument $document
   * @return void
   */
  public function getBankFile(array $form_data, AtvDocument $document) {
    $selectedBankAccountNumber = $this->getSelectedBankAccount($form_data);
    $bankFile = FALSE;
    $uploadedBankFile = FALSE;

    /** @var GrantsProfile $grantsProfile */
    $grantsProfile = $this->userInformationService->getGrantsProfileContent();

    // @todo Add file type check as well (filetype = 45 etc).
    // Check user bank files and check if correct one exists on ATV-document.
    foreach ($grantsProfile->getBankAccounts() as $bank_account) {
      $bankFile = array_find($document->getAttachments(), fn(array $attachment) => $bank_account['confirmationFile'] === $attachment['filename']);
    }

    return $bankFile;
  }

  public function getBankConfirmationFile(array $form_data, AtvDocument $document) {

//    $bank_confirmation_file_array = Helper::findMatchingBankConfirmationFile(
//      $selectedBankAccountNumber,
//      $bank_accounts,
//      $profile_files,
//    );
  }

  /**
   *
   * @param array $form_data
   * @return string
   */
  public function getSelectedBankAccount(array $form_data): string {
    return $form_data["applicant_info"]["bank_account"]["bank_account"] ?? '';
  }

  /**
   * Get all the data sources that are used in mapping.
   *
   * @param array $formData
   *   The form data.
   *
   * @param string $applicationNumber
   *   The application number.
   *
   * @param $formTypeId
   *   The form type id.
   *
   * @return array
   *   All data sources combined
   */
  private function getDataSources(array $formData, string $applicationNumber, $formTypeId): array {
    $community_official_uuid = $formData['applicant_info']['community_officials']['community_officials'][0]['official'];
    $street_name = $formData['applicant_info']['community_address']['community_address'];
    try {
      $formSettings = $this->formSettingsService->getFormSettings($formTypeId);
      $grantsProfile = $this->userInformationService->getGrantsProfileContent();

      $community_official = $grantsProfile->getCommunityOfficialByUuid($community_official_uuid);
      $address = $grantsProfile->getAddressByStreetname($street_name);
      $address['country'] = $address['country'] ?? 'Suomi';
    }
    catch (\Exception $e) {
      // User has deleted the community official and exception occurs.
      throw $e;
    }

    // Any data can be added here, and it is accessible by the mapper.
    $custom = [
      'applicant_type_id' => $this->userInformationService->getApplicantTypeId(),
      'application_number' => $applicationNumber,
      'now' => (new \DateTime())->format('Y-m-d\TH:i:s'),
      'registration_date' => $grantsProfile->getRegistrationDate(TRUE),
      'selected_address' => $address,
      'selected_community_official' => $community_official,
      'status' => 'DRAFT',
    ];

    return [
      'form_data' => $formData,
      'user' => $this->userInformationService->getUserData(),
      'company' => $this->userInformationService->getSelectedCompany(),
      'user_profile' => $this->userInformationService->getUserProfileData(),
      'grants_profile_array' => $grantsProfile->toArray(),
      'form_settings' => $formSettings->toArray(),
      'custom' => $custom,
    ];
  }


}
