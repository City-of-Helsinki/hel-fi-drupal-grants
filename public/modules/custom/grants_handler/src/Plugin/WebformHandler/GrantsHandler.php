<?php

namespace Drupal\grants_handler\Plugin\WebformHandler;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform example handler.
 *
 * @WebformHandler(
 *   id = "grants_handler",
 *   label = @Translation("Grants Handler"),
 *   category = @Translation("helfi"),
 *   description = @Translation("Grants webform handler"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class GrantsHandler extends WebformHandlerBase {

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->tokenManager = $container->get('webform.token_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'endpoint' => 'This is a custom endpoint.',
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // endpoint.
    $form['endpoint'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Endpoint settings'),
    ];
    $form['endpoint']['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rest API endpoint'),
      '#default_value' => $this->configuration['endpoint'],
      '#required' => TRUE,
    ];

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, every handler method invoked will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['endpoint'] = $form_state->getValue('endpoint');
    $this->configuration['debug'] = (bool) $form_state->getValue('debug');
  }

  /**
   * {@inheritdoc}
   */
  public function alterElements(array &$elements, WebformInterface $webform) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function overrideSettings(array &$settings, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
    if ($value = $form_state->getValue('element')) {
      $form_state->setErrorByName('element', $this->t('The element must be empty. You entered %value.', ['%value' => $value]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $endpoint = $this->configuration['endpoint'];
    $endpoint = $this->replaceTokens($endpoint, $this->getWebformSubmission());

    $applicationType = "ECONOMICGRANTAPPLICATION";
    $applicationTypeID = 29;

    $applicationNumber = "59595-JSON-VERSION-1";
    $status = "Vastaanotettu";

    $actingYear = 2021;

    $contactPerson = "Teemu Testaushenkil??";
    $street = "Annankatu 18 ?? 905";
    $city = "Helsinki";
    $postCode = "00120";
    $country = "Suomi";

    $applicantType = "2";
    $companyNumber = "5647641-1";
    $communityOfficialName = "TietoTesti Kh yleis 001 10062021";
    $communityOfficialNameShort = "TT ry";
    $registrationDate = "2021-01-01T00:00:00.000Z";
    $foundingYear = "2021";
    $home = "Helsinki";
    $webpage = "www.ttry.fi";
    $email = "tsto@ttry.fi";

    $applicantOfficials = [
      ['name' => 'nimi', 'role' => '1', 'email' => 'tsto@ttry.fi', 'phone' => '1234'],
      ['name' => 'nimi2', 'role' => '2', 'email' => 'tsto2@ttry.fi', 'phone' => '1234']
    ];

    $accountNumber = "FI9640231442000454";

    $compensationTotalAmount = "14387.00";
    $compensationPurpose = "K??ytt??tarkoituksenamme on se ett?? ... kts. liite 10.";
    $compensationExplanation = "Emme saaneet viime vuonna avustusta lainkaan.";

    $compensations = [
      ['subventionType' => '2', 'amount' => '2342.40'],
      ['subventionType' => '3', 'amount' => '3342.40']
    ];

    $otherCompensations = [
      ['issuer' => "5", "issuerName" => "Joku S????ti?? Sr.", 'year' => "2021", 'amount' => 2800, 'purpose' => "Matkakuluihin ja muihin ylim????r??isiin menoihin."]
    ];
    $otherCompensationsTotal = "2800.0";

    $benefitsPremises = "ei ole mit????n tiloja kaupungilta k??yt??ss??mme";
    $benefitsLoans = "ei ole my??sk????n lainoja taikka takauksia";

    $businessPurpose = "Meid??n toimintamme tarkoituksena on ett?? ...";
    $communityPracticesBusiness = "false";
    $membersApplicantPersonGlobal = 50;
    $membersApplicantPersonLocal = 45;
    $membersSubdivisionPersonGlobal = 20;
    $membersSubdivisionCommunityGlobal = 2;
    $membersSubdivisionPersonLocal = 10;
    $membersSubdivisionCommunityLocal = 1;
    $membersSubcommunityPersonGlobal = 3;
    $membersSubcommunityCommunityGlobal = 3;
    $membersSubcommunityPersonLocal = 29;
    $membersSubcommunityCommunityLocal = 3;
    $feePerson = 32;
    $feeCommunity = 32;
    $additionalInformation = "T??ll?? kertaa ei ole muuta ilmoitettavaa t??h??n hakemukseen";

    $senderInfoFirstname = "Testaaja";
    $senderInfoLastname = "Tiina";
    $senderInfoPersonID = "123456-7890";
    $senderInfoUserID = "Testatii";
    $senderInfoEmail = "tiina.testaaja@testiyhdistys.fi";

    $attachments = [[
      'description' => "Pankin ilmoitus tilinomistajasta tai tiliotekopio (uusilta hakijoilta tai pankkiyhteystiedot muuttuneet) *",
      'filename' => "01_pankin_ilmoitus_tilinomistajast_.docx",
      'filetype' => 1
    ], [
      'description' => "2 Pankin ilmoitus tilinomistajasta tai tiliotekopio (uusilta hakijoilta tai pankkiyhteystiedot muuttuneet) *",
      'filename' => "02_pankin_ilmoitus_tilinomistajast_.docx",
      'filetype' => 2
    ]];

    $applicationInfoArray = [
      (object) [
        "ID" => "applicationType",
        "label" => "Hakemustyyppi",
        "value" => $applicationType,
        "valueType" => "string",
      ],
      (object) [
        "ID" => "applicationTypeID",
        "label" => "Hakemustyypin numero",
        "value" => $applicationTypeID,
        "valueType" => "int",
      ],
      (object) [
        "ID" => "formTimeStamp",
        "label" => "Hakemuksen/sanoman l??hetyshetki",
        "value" => gmdate("Y-m-d\TH:i:s.v\Z", $webform_submission->getCreatedTime()),
        "valueType" => "datetime",
      ],
      (object) [
        "ID" => "applicationNumber",
        "label" => "Hakemusnumero",
        "value" => $applicationNumber,
        "valueType" => "string",
      ],
      (object) [
        "ID" => "status",
        "label" => "Tila",
        "value" => $status,
        "valueType" => "string",
      ],
      (object) [
        "ID" => "actingYear",
        "label" => "Hakemusvuosi",
        "value" => $actingYear,
        "valueType" => "int",
      ]
    ];

    $currentAddressInfoArray = [
      (object) [
        "ID" =>  "contactPerson",
        "label" => "Yhteyshenkil??",
        "value" => $contactPerson,
        "valueType" => "string"
      ],
      (object) [
        "ID" => "street",
        "label" => "Katuosoite",
        "value" => $street,
        "valueType" => "string"
      ],
      (object) [
        "ID" => "city",
        "label" => "Postitoimipaikka",
        "value" => $city,
        "valueType" => "string"
      ],
      (object) [
        "ID" => "postCode",
        "label" => "Postinumero",
        "value" => $postCode,
        "valueType" => "string"
	    ],
      (object) [
        "ID" => "country",
        "label" => "Maa",
        "value" => $country,
        "valueType" => "string"
      ],
    ];

    $applicantInfoArray = [
      (object) [
        "ID" => "applicantType",
        "label" => "Hakijan tyyppi",
        "value" => $applicantType,
        "valueType" => "string"
	    ],
      (object) [
        "ID" => "companyNumber",
        "label" => "Rekisterinumero",
        "value" => $companyNumber,
        "valueType" => "string"
	    ],
      (object) [
        "ID" => "communityOfficialName",
        "label" => "Yhteis??n nimi",
        "value" => $communityOfficialName,
        "valueType" => "string"
	    ],
      (object) [
        "ID" => "communityOfficialNameShort",
        "label" => "Yhteis??n lyhenne",
        "value" => $communityOfficialNameShort,
        "valueType" => "string"
	    ],
      (object) [
        "ID" => "registrationDate",
        "label" => "Rekister??imisp??iv??",
        "value" => $registrationDate,
        "valueType" => "datetime"
	    ],
      (object) [
        "ID" => "foundingYear",
        "label" => "Perustamisvuosi",
        "value" => $foundingYear,
        "valueType" => "int"
	    ],
      (object) [
        "ID" => "home",
        "label" => "Kotipaikka",
        "value" => $home,
        "valueType" => "string"
	    ],
      (object) [
        "ID" => "homePage",
        "label" => "www-sivut",
        "value" => $webpage,
        "valueType" => "string"
	    ],
      (object) [
        "ID" => "email",
        "label" => "S??hk??postiosoite",
        "value" => $email,
        "valueType" => "string"
      ]
    ];


    $applicantOfficialsArray = [];
    foreach ($applicantOfficials as $official) {
      $applicantOfficialsArray[] = [
      (object) [
          "ID" => "email",
          "label" => "S??hk??posti",
          "value" => $official['email'],
          "valueType" => "string"
        ],
        (object) [
          "ID" => "role",
          "label" => "Rooli",
          "value" => $official['role'],
          "valueType" => "string"
        ],
        (object) [
          "ID" => "name",
          "label" => "Nimi",
          "value" => $official['name'],
          "valueType" => "string"
        ],
        (object) [
          "ID" => "phone",
          "label" => "Puhelinnumero",
          "value" => $official['phone'],
          "valueType" => "string"
        ]
      ];

      $compensationArray = [];

      foreach ($compensations as $compensation) {
        $compensationArray[] = [
          (object) [
						"ID" => "subventionType",
						"label" => "Avustuslaji",
						"value" => $compensation['subventionType'],
						"valueType" => "string"
          ],
          (object) [
              "ID" => "amount",
              "label" => "Euroa",
              "value" => $compensation['amount'],
              "valueType" => "float"
          ]
          ];
      };

    	$bankAccountArray = [
        (object) [
				"ID" => "accountNumber",
				"label" => "Tilinumero",
				"value" => $accountNumber,
				"valueType" => "string"
        ]
        ];

    $compensationInfo = (object) [
			"generalInfoArray" => [
        (object) [
					"ID" => "totalAmount",
					"label" => "Haettavat avustukset yhteens??",
					"value" => $compensationTotalAmount,
					"valueType" => "float"
        ],
        (object) [
					"ID" => "purpose",
					"label" => "Haetun avustuksen k??ytt??tarkoitus",
					"value" => $compensationPurpose,
					"valueType" => "string"
        ],
        (object) [
					"ID" => "explanation",
					"label" => "Selvitys edellisen avustuksen k??yt??st??",
					"value" => $compensationExplanation,
					"valueType" => "string"
        ]
        ],
			"compensationArray" => $compensationArray,

    ];


    $otherCompesationsArray = [];
    foreach ($otherCompensations as $compensation) {
      $otherCompesationsArray[] = [
        (object) [
						"ID" => "issuer",
						"label" => "My??nt??j??",
						"value" => $compensation['issuer'],
						"valueType" => "string"
        ],
        (object) [
						"ID" => "issuerName",
						"label" => "My??nt??j??n nimi",
						"value" => $compensation['issuerName'],
						"valueType" => "string"
        ],
        (object) [
						"ID" => "year",
						"label" => "Vuosi",
						"value" => $compensation['year'],
						"valueType" => "string"
        ],
        (object) [
						"ID" => "amount",
						"label" => "Euroa",
						"value" => $compensation['amount'],
						"valueType" => "float"
        ],
        (object) [
						"ID" => "purpose",
						"label" => "Tarkoitus",
						"value" => $compensation['purpose'],
						"valueType" => "string"
				  ]
				];
    }

		$otherCompensationsInfo = (object) [
			"otherCompensationsArray" =>
        $otherCompesationsArray,
			"otherCompensationsTotal" => $otherCompensationsTotal
    ];


		$benefitsInfoArray = [
      (object) [
				"ID" => "premises",
				"label" => "Tilat, jotka kaupunki on antanut korvauksetta tai vuokrannut hakijan k??ytt????n (osoite, pinta-ala ja tiloista maksettava vuokra ???/kk",
				"value" => $benefitsPremises,
				"valueType" => "string"
        ],
        (object) [
				"ID" => "loans",
				"label" => "Kaupungilta saadut lainat ja/tai takaukset",
				"value" => $benefitsLoans,
				"valueType" => "string"
        ]
        ];


      $activitiesInfoArray = [
        (object) [
				"ID" => "businessPurpose",
				"label" => "Toiminnan tarkoitus",
				"value" => $businessPurpose,
				"valueType" => "string"
        ],
        (object) [
				"ID" => "communityPracticesBusiness",
				"label" => "Yhteis?? harjoittaa liiketoimintaa",
				"value" => $communityPracticesBusiness,
				"valueType" => "bool"
        ],
        (object) [
				"ID" => "membersApplicantPersonGlobal",
				"label" => "Hakijayhteis??, henkil??j??seni??",
				"value" => $membersApplicantPersonGlobal,
				"valueType" => "int"
        ],
        (object) [
				"ID" => "membersApplicantPersonLocal",
				"label" => "Hakijayhteis??, helsinkil??isi?? henkil??j??seni??",
				"value" => $membersApplicantPersonLocal,
				"valueType" => "int"
        ],
        (object) [
				"ID" => "membersSubdivisionPersonGlobal",
				"label" => "Alayhdistykset, henkil??j??seni??",
				"value" => $membersSubdivisionPersonGlobal,
				"valueType" => "int"
        ],
        (object) [
				"ID" => "membersSubdivisionCommunityGlobal",
				"label" => "Alayhdistykset, yhteis??j??seni??",
				"value" => $membersSubdivisionCommunityGlobal,
				"valueType" => "int"
        ],
        (object) [
				"ID" => "membersSubdivisionPersonLocal",
				"label" => "Alayhdistykset, helsinkil??isi?? henkil??j??seni??",
				"value" => $membersSubdivisionPersonLocal,
				"valueType" => "int"
        ],
        (object) [
				"ID" => "membersSubdivisionCommunityLocal",
				"label" => "Alayhdistykset, helsinkil??isi?? yhteis??j??seni??",
				"value" => $membersSubdivisionCommunityLocal,
				"valueType" => "int"
        ],
        (object) [
				"ID" => "membersSubcommunityPersonGlobal",
				"label" => "Alaosastot, henkil??j??seni??",
				"value" => $membersSubcommunityPersonGlobal,
				"valueType" => "int"
        ],
        (object) [
				"ID" => "membersSubcommunityCommunityGlobal",
				"label" => "Alaosastot, yhteis??j??seni??",
				"value" => $membersSubcommunityCommunityGlobal,
				"valueType" => "int"
        ],
        (object) [
				"ID" => "membersSubcommunityPersonLocal",
				"label" => "Alaosastot, helsinkil??isi?? henkil??j??seni??",
				"value" => $membersSubcommunityPersonLocal,
				"valueType" => "int"
        ],
        (object) [
				"ID" => "membersSubcommunityCommunityLocal",
				"label" => "Alaosastot, helsinkil??isi?? yhteis??j??seni??",
				"value" => $membersSubcommunityCommunityLocal,
				"valueType" => "int"
        ],
        (object) [
				"ID" => "feePerson",
				"label" => "J??senmaksun suuruus, Henki??j??sen euroa",
				"value" => $feePerson,
				"valueType" => "float"
        ],
        (object) [
				"ID" => "feeCommunity",
				"label" => "J??senmaksun suuruus, Yhteis??j??sen euroa",
				"value" => $feeCommunity,
				"valueType" => "float"
        ]
        ];

    	$senderInfoArray = [
        (object) [
				"ID" => "firstname",
				"label" => "Etunimi",
				"value" => $senderInfoFirstname,
				"valueType" => "string"
        ],
        (object) [
				"ID" => "lastname",
				"label" => "Sukunimi",
				"value" => $senderInfoLastname,
				"valueType" => "string"
        ],
        (object) [
				"ID" => "personID",
				"label" => "Henkil??tunnus",
				"value" => $senderInfoPersonID,
				"valueType" => "string"
        ],
        (object) [
				"ID" => "userID",
				"label" => "K??ytt??j??tunnus",
				"value" => $senderInfoUserID,
				"valueType" => "string"
        ],
        (object) [
				"ID" => "email",
				"label" => "S??hk??posti",
				"value" => $senderInfoEmail,
				"valueType" => "string"
        ]
        ];

    $compensationObject = (object) [
      "applicationInfoArray" => $applicationInfoArray,
      "currentAddressInfoArray" => $currentAddressInfoArray,
      "applicantInfoArray" => $applicantInfoArray,
      "applicantOfficialsArray" => $applicantOfficialsArray,
      "bankAccountArray" => $bankAccountArray,
      "compensationInfo" => $compensationInfo,
      "otherCompensationsInfo" => $otherCompensationsInfo,
      "benefitsInfoArray" => $benefitsInfoArray,
      "activitiesInfoArray" => $activitiesInfoArray,
		  "additionalInformation" => $additionalInformation,
      "senderInfoArray" => $senderInfoArray,
    ];


	$attachmentsArray = [];
  foreach ($attachments as $attachment) {
    $attachmentsArray[] = [
      (object) [
        "ID" => "description",
        "value" => $attachment['description'],
        "valueType" => "string"
      ],
      (object) [
        "ID" => "fileName",
        "value" => $attachment['filename'],
        "valueType" => "string"
      ],
      (object) [
        "ID" => "fileType",
        "value" => $attachment['filetype'],
        "valueType" => "int"
      ]
    ];
  }

    $attachmentsInfoObject = [
      "attachmentsArray" => $attachmentsArray
    ];
    $submitObject = (object) ['compensation' => $compensationObject, 'attachmentsInfo' => $attachmentsInfoObject];
    $submitObject->attachmentsInfo = $attachmentsInfoObject;
    $submitObject->formUpdate = FALSE;
    $myJSON = json_encode($submitObject);
    $this->messenger()->addStatus(Markup::create($myJSON), FALSE);

    $this->debug(__FUNCTION__);
  }
}

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$values) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postLoad(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preDelete(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $this->debug(__FUNCTION__, $update ? 'update' : 'insert');
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessConfirmation(array &$variables) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function createHandler() {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function updateHandler() {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteHandler() {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function createElement($key, array $element) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function updateElement($key, array $element, array $original_element) {
    $this->debug(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteElement($key, array $element) {
    $this->debug(__FUNCTION__);
  }

  /**
   * Display the invoked plugin method to end user.
   *
   * @param string $method_name
   *   The invoked method name.
   * @param string $context1
   *   Additional parameter passed to the invoked method name.
   */
  protected function debug($method_name, $context1 = NULL) {
    if (!empty($this->configuration['debug'])) {
      $t_args = [
        '@id' => $this->getHandlerId(),
        '@class_name' => get_class($this),
        '@method_name' => $method_name,
        '@context1' => $context1,
      ];
      $this->messenger()->addWarning($this->t('Invoked @id: @class_name:@method_name @context1', $t_args), TRUE);
    }
  }

}
