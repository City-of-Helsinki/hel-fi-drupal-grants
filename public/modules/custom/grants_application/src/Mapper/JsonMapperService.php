<?php

declare(strict_types=1);

namespace Drupal\grants_application\Mapper;


use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_application\Helper;
use Drupal\grants_application\User\UserInformationService;
use Drupal\helfi_atv\AtvDocument;

/**
 * Mapping related logic.
 */
final class JsonMapperService {

  private JsonMapper $mapper;

  public function __construct(
    private readonly UserInformationService $userInformationService,
    private readonly FormSettingsService $formSettingsService,
  ) {
  }

  /**
   * Map field data.
   *
   * @param string $formTypeId
   *   The form type id.
   * @param string $applicationNumber
   *   The application number.
   * @param array $formData
   *   The form data.
   *
   * @return array
   *   Mapped data.
   */
  public function handleMapping(
    string $formTypeId,
    string $applicationNumber,
    array $formData,
    array $bankFile,
    bool $isDraft,
    string $selectedCompanyType
  ): array {
    // @todo Fix.
    $this->mapper = new JsonMapper();
    $dataSources = $this->getDataSources($formData, $applicationNumber, $formTypeId);

    // Mappings are divided into common fields (by mandate) and form specific fields.
    $commonFieldMapping = json_decode(file_get_contents(__DIR__ . '/Mappings/common/' . $selectedCompanyType . '.json'), TRUE);
    $this->mapper->setMappings($commonFieldMapping);
    $mappedCommonFields = $this->mapper->map($dataSources);

    $mapping = json_decode(file_get_contents(__DIR__ . '/Mappings/' . "ID$formTypeId.json"), TRUE);
    $this->mapper->setMappings($mapping);
    $mappedData = $this->mapper->map($dataSources);

    // Files are mapped separately.
    $mappedData = array_merge_recursive($mappedCommonFields, $mappedData);
    $mappedData = $this->addFileData($mappedData, $bankFile, $dataSources);

    // Make sure the data contains everything we need.
    // Only on first submission this must be false.
    $mappedData['formUpdate'] = !$isDraft;

    foreach(['statusUpdates', 'events', 'messages'] as $field) {
      if (!isset($mappedData[$field])) {
        $mappedData[$field] = [];
      }
    }

    return $mappedData;
  }

  /**
   * Patch request has enough differences
   *
   * @param string $formTypeId
   * @param string $applicationNumber
   * @param array $formData
   * @param string $selectedCompanyType
   * @param array $oldDocument
   * @return array
   * @throws \Exception
   */
  public function handleMappingForPatchRequest(
    string $formTypeId,
    string $applicationNumber,
    array $formData,
    string $selectedCompanyType,
    array $oldDocument,
  ): array {
    $mappingFileName = "ID$formTypeId.json";
    $dataSources = $this->getDataSources($formData, $applicationNumber, $formTypeId);

    // @todo Fix.
    $this->mapper = new JsonMapper();

    // Mappings are divided into common fields (by mandate) and form specific fields.
    $commonFieldMapping = json_decode(file_get_contents(__DIR__ . '/Mappings/common/' . $selectedCompanyType . '.json'), TRUE);
    $this->mapper->setMappings($commonFieldMapping);
    $mappedCommonFields = $this->mapper->map($dataSources);

    $mapping = json_decode(file_get_contents(__DIR__ . '/Mappings/' . $mappingFileName), TRUE);
    $this->mapper->setMappings($mapping);
    $mappedData = $this->mapper->map($dataSources);

    $oldFiles = $oldDocument['content']['attachmentsInfo']['attachmentsArray'];
    $newFiles = $this->mapper->mapFiles($dataSources);
    $newFiles = $newFiles['attachmentsInfo']['attachmentsArray'] ?? [];
    $patchedFiles = $this->patchMappedFiles(
      $oldFiles,
      $newFiles
    );

    $mappedData = array_merge_recursive($mappedCommonFields, $mappedData);
    $mappedData['attachmentsInfo']['attachmentsArray'] = $patchedFiles;

    $mappedData['events'] = $oldDocument['content']['events'];
    $mappedData['messages'] = $oldDocument['content']['messages'];
    $mappedData['statusUpdates'] = $oldDocument['content']['statusUpdates'];
    $mappedData['formUpdate'] = TRUE;

    // After first submit, we must just copy the status value edited by integration.
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
    $mappedData = array_merge($mappedData, $mappedFileData);

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

  /**
   * Get complete list of files to send to Avus2.
   *
   * When user sends patch-request, the files must be handled correctly.
   * User may not delete files but may add new files. Also, the bank file must
   * exist. Therefore, we pick all unique files from both old and new
   * submission to make sure we have everything.
   *
   * @param array $oldFiles
   *   The mapped files from old atv-document.
   * @param array $newFiles
   *   The freshly mapped files.
   *
   * @return array
   *   The files array that should be put to attachmentsInfo.attachmentsArray.
   */
  public function patchMappedFiles(array $oldFiles, array $newFiles): array {
    $uniqueFiles = [];

    if ($bankFile = $this->mapper->getMappedBankFile($oldFiles)) {
      $uniqueFiles[] = $bankFile;
    }

    foreach (array_merge($oldFiles, $newFiles) as $fileFieldArray) {
      $integrationIdValueArray = array_find($fileFieldArray, fn($fa) => $fa['ID'] === 'integrationID');

      if ($integrationIdValueArray) {
        $fileFound = FALSE;
        foreach ($uniqueFiles as $uniqueFileFieldArray) {
          $uniqueIntegrationIdField = array_find($uniqueFileFieldArray, fn($item) => $item['ID'] === 'integrationID');
          if ($uniqueIntegrationIdField && $uniqueIntegrationIdField['value'] === $integrationIdValueArray['value']) {
            $fileFound = TRUE;
          }
        }

        if (!$fileFound) {
          $uniqueFiles[] = $fileFieldArray;
        }
      }
    }

    return $uniqueFiles;
  }


}
