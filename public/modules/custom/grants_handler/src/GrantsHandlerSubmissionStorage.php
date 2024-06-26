<?php

namespace Drupal\grants_handler;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_metadata\DocumentContentMapper;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Override loading of WF submission from data from ATV.
 *
 * This could be used overriding the saving as well,
 * but for now this is enough.
 */
class GrantsHandlerSubmissionStorage extends WebformSubmissionStorage {

  /**
   * Atv service object.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Schema mapper.
   *
   * @var \Drupal\grants_metadata\AtvSchema
   */
  protected AtvSchema $atvSchema;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * Access to user profile data.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData;

  /**
   * If same data is requested multiple times, it's cached here.
   *
   * @var array
   */
  protected array $data;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type): WebformSubmissionStorage|EntityHandlerInterface {

    /** @var self|static $instance */
    $instance = parent::createInstance($container, $entity_type);

    /** @var \Drupal\helfi_atv\AtvService $atvService */
    $atvService = $container->get('helfi_atv.atv_service');
    $instance->atvService = $atvService;

    /** @var \Drupal\grants_metadata\AtvSchema $atvSchema */
    $atvSchema = \Drupal::service('grants_metadata.atv_schema');
    $instance->atvSchema = $atvSchema;

    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = \Drupal::currentUser();
    $instance->account = $account;

    /** @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliUserData */
    $helsinkiProfiiliUserData = \Drupal::service('helfi_helsinki_profiili.userdata');
    $instance->helsinkiProfiiliUserData = $helsinkiProfiiliUserData;

    $instance->data = [];

    /** @var static $instance */
    return $instance;
  }

  /**
   * Make sure no form data is saved.
   *
   * Maybe we could save data to ATV here? Probably not though, depends how
   * often this is called.
   *
   * @inheritdoc
   */
  public function saveData(WebformSubmissionInterface $webform_submission, $delete_first = TRUE) {

  }

  /**
   * Turn AtvDocument into webform submission.
   *
   * There is no need to do more access checks here because document
   * is already loaded.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   Submission matching the given data.
   */
  public function loadByAtvDocument(string $serial, string $webformId, AtvDocument $document): ?WebformSubmissionInterface {
    $values = [
      'serial' => $serial,
      'webform_id' => $webformId,
    ];
    try {
      // Build a query to fetch the entity IDs.
      // This is based on Drupal\Core\Entity\EntityStorageBase.
      $entityQuery = $this->getQuery();
      $entityQuery->accessCheck(FALSE);
      $this->buildPropertyQuery($entityQuery, $values);
      $result = $entityQuery->execute();

      /** @var \Drupal\webform\WebformSubmissionInterface[] $submissionArray */
      $submissionArray = $this->loadMultiple($result);
      $submission = reset($submissionArray);
      if (!$submission) {
        return NULL;
      }
      $docArray = $document->toArray();
      $id = AtvSchema::extractDataForWebForm(
        $docArray['content'], ['applicationNumber']
      );

      if (!isset($id['applicationNumber']) || empty($id['applicationNumber'])) {
        throw new \Exception('ATV Document does not contain application number.');
      }
      $appData = self::setAtvDataToSubmission($document, $submission);
      $this->data[$submission->id()] = $appData;
    }
    catch (\Exception $exception) {
      $this->loggerFactory->get('GrantsHandlerSubmissionStorage')
        ->error('Document %appno not found when loading WebformSubmission: %submission. Error: %msg',
          [
            '%appno' => $id['applicationNumber'],
            '%submission' => $submission->uuid(),
            '%msg' => $exception->getMessage(),
          ]);
      $submission->setData([]);
    }
    return $submission;
  }

  /**
   * Take ATV data and put it in submission.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   ATV Document.
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   Webform submission.
   *
   * @return array
   *   Data that was set to submission.
   */
  public static function setAtvDataToSubmission(AtvDocument $document, WebformSubmissionInterface $submission): array {
    $dataDefinition = ApplicationHandler::getDataDefinition($document->getType());

    $sData = DocumentContentMapper::documentContentToTypedData(
      $document->getContent(),
      $dataDefinition,
      $document->getMetadata()
    );

    $sData['messages'] = ApplicationHandler::parseMessages($sData);

    $submission->setData($sData);
    return $sData;
  }

  /**
   * Save webform submission data from the 'webform_submission_data' table.
   *
   * @param array $webform_submissions
   *   An array of webform submissions.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function loadData(array &$webform_submissions) {
    parent::loadData($webform_submissions);
    $userRoles = $this->account->getRoles();

    // Check that we have required role.
    if (!in_array('helsinkiprofiili', $userRoles)) {
      return;
    }
    $userAuthLevel = $this->helsinkiProfiiliUserData->getAuthenticationLevel();
    // Load things only with strong authentication.
    if ($userAuthLevel !== 'strong') {
      return;
    }
    /** @var \Drupal\webform\Entity\WebformSubmission $submission */
    foreach ($webform_submissions as $submission) {
      if (!empty($this->data[$submission->id()])) {
        $submission->setData($this->data[$submission->id()]);
        continue;
      }
      $applicationNumber = '';
      try {
        $applicationNumber = ApplicationHandler::createApplicationNumber($submission);
        $results = $this->atvService->searchDocuments(
          [
            'transaction_id' => $applicationNumber,
            'lookfor' => 'appenv:' . ApplicationHandler::getAppEnv(),
          ]
        );
        /** @var \Drupal\helfi_atv\AtvDocument $document */
        $document = reset($results);

        if (!$document) {
          $applicationNumber = ApplicationHandler::createApplicationNumber($submission, TRUE);
          $results = $this->atvService->searchDocuments(
            [
              'transaction_id' => $applicationNumber,
              'lookfor' => 'appenv:' . ApplicationHandler::getAppEnv(),
            ]
          );
          /** @var \Drupal\helfi_atv\AtvDocument $document */
          $document = reset($results);
        }

        if (!$document) {
          throw new \Exception('Submission data load failed.');
        }

        $docArray = $document->toArray();
        $id = AtvSchema::extractDataForWebForm(
          $docArray['content'], ['applicationNumber']
        );

        if (!isset($id['applicationNumber']) || empty($id['applicationNumber'])) {
          continue;
        }

        $appData = self::setAtvDataToSubmission($document, $submission);
        $this->data[$submission->id()] = $appData;

      }
      catch (\Exception $exception) {
        $this->loggerFactory->get('GrantsHandlerSubmissionStorage')
          ->error('Document %appno not found when loading WebformSubmission: %submission. Error: %msg',
            [
              '%appno' => $applicationNumber,
              '%submission' => $submission->uuid(),
              '%msg' => $exception->getMessage(),
            ]);
        $submission->setData([]);
      }
    }

  }

}
