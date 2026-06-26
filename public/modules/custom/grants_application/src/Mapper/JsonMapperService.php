<?php

declare(strict_types=1);

namespace Drupal\grants_application\Mapper;

use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_application\Helper;
use Drupal\grants_application\User\UserInformationService;
use Drupal\helfi_atv\AtvDocument;

/**
 * Mapping related logic.
 */
class JsonMapperService {

  /**
   * The mapper.
   *
   * @var JsonMapper
   */
  private JsonMapper $mapper;

  public function __construct(
    private readonly UserInformationService $userInformationService,
    private readonly FormSettingsServiceInterface $formSettingsService,
  ) {
  }

  /**
   * Map field data.
   *
   * @param string $formTypeId
   *   The form type id.
   * @param string $formIdentifier
   *   The form type id.
   * @param string $applicationNumber
   *   The application number.
   * @param array $formData
   *   The form data.
   * @param array $bankFile
   *   The bank file.
   * @param bool $isDraft
   *   Is draft.
   * @param string $selectedCompanyType
   *   The selected company type.
   *
   * @return array
   *   Mapped data.
   */
  public function handleMapping(
    string $formTypeId,
    string $formIdentifier,
    string $applicationNumber,
    array $formData,
    array $bankFile,
    bool $isDraft,
    string $selectedCompanyType,
  ): array {
    $this->mapper = new JsonMapper();
    $dataSources = $this->getDataSources($formData, $applicationNumber, $formTypeId, $formIdentifier);

    // Mappings are divided into common fields (by mandate)
    // and form specific fields, lets run the mapping in multiple stages.
    // Start with common fields.
    $commonFieldMapping = json_decode(file_get_contents(__DIR__ . '/Mappings/common/' . $selectedCompanyType . '.json'), TRUE);
    $this->mapper->setMappings($commonFieldMapping);
    $mappedCommonFields = $this->mapper->map($dataSources);

    // Then map the form specific fields.
    $filePath = sprintf('%s/%s/ID%s/%s.json', __DIR__, 'Mappings', $formTypeId, $formIdentifier);
    $mapping = json_decode(file_get_contents($filePath), TRUE);
    $this->mapper->setMappings($mapping);
    $mappedData = $this->mapper->map($dataSources);

    // Files are mapped separately.
    $mappedData = array_merge_recursive($mappedCommonFields, $mappedData);
    $mappedData = $this->addFileData($mappedData, $bankFile, $dataSources);

    // Make sure the data contains everything we need.
    // Only on first submission this must be false.
    $mappedData['formUpdate'] = !$isDraft;

    foreach (['statusUpdates', 'events', 'messages'] as $field) {
      if (!isset($mappedData[$field])) {
        $mappedData[$field] = [];
      }
    }

    return $mappedData;
  }

  /**
   * Patch request has enough differences.
   *
   * @param string $formTypeId
   *   The form type id.
   * @param string $formIdentifier
   *   The form identifier.
   * @param string $applicationNumber
   *   The application number.
   * @param array $formData
   *   The form data.
   * @param string $selectedCompanyType
   *   The selected company.
   * @param array $oldDocument
   *   The old document.
   *
   * @return array
   *   Mapped data.
   *
   * @throws \Exception
   */
  public function handleMappingForPatchRequest(
    string $formTypeId,
    string $formIdentifier,
    string $applicationNumber,
    array $formData,
    string $selectedCompanyType,
    array $oldDocument,
  ): array {
    $dataSources = $this->getDataSources($formData, $applicationNumber, $formTypeId, $formIdentifier);

    // @todo Fix.
    $this->mapper = new JsonMapper();

    // Mappings are divided into common fields (by mandate)
    // and form specific fields.
    $commonFieldMapping = json_decode(file_get_contents(__DIR__ . '/Mappings/common/' . $selectedCompanyType . '.json'), TRUE);
    $this->mapper->setMappings($commonFieldMapping);
    $mappedCommonFields = $this->mapper->map($dataSources);

    $filePath = sprintf('%s/%s/ID%s/%s.json', __DIR__, 'Mappings', $formTypeId, $formIdentifier);
    $mapping = json_decode(file_get_contents($filePath), TRUE);
    $this->mapper->setMappings($mapping);
    $mappedData = $this->mapper->map($dataSources);

    $oldFiles = $oldDocument['content']['attachmentsInfo']['attachmentsArray'];
    $newFiles = $this->mapper->mapFiles($dataSources);
    $newFiles = $newFiles['attachmentsInfo']['attachmentsArray'] ?? [];
    $patchedFiles = $this->mapper->patchMappedFiles(
      $oldFiles,
      $newFiles
    );

    $mappedData = array_merge_recursive($mappedCommonFields, $mappedData);
    $mappedData['attachmentsInfo']['attachmentsArray'] = $patchedFiles;

    $mappedData['events'] = $oldDocument['content']['events'];
    $mappedData['messages'] = $oldDocument['content']['messages'];
    $mappedData['statusUpdates'] = $oldDocument['content']['statusUpdates'];
    $mappedData['formUpdate'] = TRUE;

    // After first submit, just copy the status value edited by integration.
    if ($oldStatus = $this->mapper->getStatusValue($oldDocument)) {
      $this->mapper->setStatusValue($mappedData, $oldStatus);
    }

    return $mappedData;
  }

  /**
   * Add file mappings to correct places.
   *
   * @param array $mappedData
   *   The mapped data.
   * @param array $bankFile
   *   The bank file.
   * @param array $dataSources
   *   The data sources.
   *
   * @return array
   *   The mapped data with files.
   */
  private function addFileData(array $mappedData, array $bankFile, array $dataSources): array {
    $formData = $dataSources['form_data'];
    $mappedBankFile = $this->mapper->mapBankFile($this->getSelectedBankAccount($formData), $bankFile);
    $mappedFileData = $this->mapper->mapFiles($dataSources);
    $mappedData = array_merge_recursive($mappedData, $mappedFileData);

    $mappedData['attachmentsInfo']['attachmentsArray'][] = $mappedBankFile;
    return $mappedData;
  }

  /**
   * Get the selected bank account file from grants profile.
   *
   * @param array $formData
   *   The form data.
   *
   * @return array
   *   File data array.
   */
  public function getSelectedBankFile(array $formData): array {
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
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The document.
   *
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
          // One of the profile bank accounts can be found from the document.
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Get selected bank account.
   *
   * @param array $form_data
   *   The form data.
   *
   * @return string
   *   The selected bank account number.
   */
  public function getSelectedBankAccount(array $form_data): string {
    return $form_data["applicant_info"]["bank_account"]["bank_account"] ?? '';
  }

  /**
   * Get all the data sources that are used in mapping.
   *
   * @param array $formData
   *   The form data.
   * @param string $applicationNumber
   *   The application number.
   * @param string|int $formTypeId
   *   The form type id.
   * @param string $formIdentifier
   *   The form identifier.
   *
   * @return array
   *   All data sources combined
   */
  private function getDataSources(array $formData, string $applicationNumber, string|int $formTypeId, string $formIdentifier): array {
    $community_official_uuids = $formData['applicant_info']['community_officials']['community_officials'] ?? [];
    $street_name = $formData['applicant_info']['community_address']['community_address'] ?? '';
    $bank_account_number = $formData['applicant_info']['bank_account']['bank_account'] ?? '';

    $community_officials = [];
    $selected_bank_account = [];
    $grantsProfile = $this->userInformationService->getGrantsProfileContent();

    foreach ($community_official_uuids as $community_official_uuid) {
      try {
        $community_official = $grantsProfile->getCommunityOfficialByUuid($community_official_uuid);
      }
      catch (\Exception $e) {
        continue;
      }
      if ($community_official) {
        unset($community_official['official_id']);
        $community_officials[] = $community_official;
      }
    }

    foreach ($grantsProfile->getBankAccounts() as $bankAccount) {
      if ($bank_account_number !== $bankAccount['bankAccount']) {
        continue;
      }

      $selected_bank_account = $bankAccount;
      break;
    }

    try {
      $formSettings = $this->formSettingsService->getFormSettings($formTypeId, $formIdentifier);
      $addresses = $grantsProfile->getAddresses();
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
      'addresses' => $addresses,
      'selected_address' => $address,
      'selected_community_officials' => $community_officials,
      'selected_bank_account' => $selected_bank_account,
      'status' => 'DRAFT',
    ];

    return [
      'form_data' => $formData,
      'user' => (array) $this->userInformationService->getUserData(),
      'company' => $this->userInformationService->getSelectedCompany(),
      'user_profile' => $this->userInformationService->getUserProfileData(),
      'grants_profile_array' => $grantsProfile->toArray(),
      'form_settings' => $formSettings->toArray(),
      'custom' => $custom,
    ];
  }

}
