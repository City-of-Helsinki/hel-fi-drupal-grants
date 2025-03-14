<?php

namespace Drupal\grants_club_section\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_club_section\Validator\FieldValueValidator;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'club_section_composite'.
 *
 * Webform composites contain a group of sub-elements.
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. club_section_composite)
 *
 * @FormElement("club_section_composite")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 */
class ClubSectionComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'club_section_composite'];
  }

  /**
   * Process default values and values from submitted data.
   *
   * @param array $element
   *   Element that is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Full form.
   *
   * @return array[]
   *   Form API element for webform element.
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {

    $element['#tree'] = TRUE;
    $element = parent::processWebformComposite($element, $form_state, $complete_form);

    _grants_handler_process_multivalue_errors($element, $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];
    $tOpts = ['context' => 'grants_club_section'];
    $id = Html::getUniqueId('club-section');

    $sportValuesForState = [
      ['value' => 'Other combat sport'],
      ['value' => 'Other sport'],
      ['value' => t('Other combat sport', [], [...$tOpts, 'langcode' => 'fi'])],
      ['value' => t('Other sport', [], [...$tOpts, 'langcode' => 'fi'])],
      ['value' => t('Other combat sport', [], [...$tOpts, 'langcode' => 'sv'])],
      ['value' => t('Other sport', [], [...$tOpts, 'langcode' => 'sv'])],
    ];

    $elements['sectionName'] = [
      '#type' => 'select',
      '#title' => t('Sport', [], $tOpts),
      '#options' => array_combine(self::getOptions(), self::getOptions()),
      '#empty_option' => t('- Select -'),
      '#sort_options' => TRUE,
      '#sort_start' => 3,
      '#required' => TRUE,
      '#attributes' => [
        'data-club-section-id' => $id,
      ],
    ];

    $elements['sectionOther'] = [
      '#type' => 'textfield',
      '#title' => t('Other sport', [], $tOpts),
      '#states' => [
        'visible' => [
          [":input[data-club-section-id=\"{$id}\"]" => $sportValuesForState],
        ],
        'required' => [
          [":input[data-club-section-id=\"{$id}\"]" => $sportValuesForState],
        ],
      ],
      '#element_validate' => [
        [FieldValueValidator::class, 'validateSectionOther'],
      ],
    ];

    $elements['men'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Men from Helsinki (20-63 years)', [], $tOpts),
      '#prefix' => '<div class="club-section__participants">',
      '#element_validate' => [
        [FieldValueValidator::class, 'validate'],
      ],
    ];

    $elements['women'] = [
      '#type' => 'textfield',
      '#title' => t('Women from Helsinki (20-63 years)', [], $tOpts),
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#element_validate' => [
        [FieldValueValidator::class, 'validate'],
      ],
    ];

    $elements['adultOthers'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Others from Helsinki (20-63 years)', [], $tOpts),
      '#suffix' => '</div>',
      '#element_validate' => [
        [FieldValueValidator::class, 'validate'],
      ],
    ];

    $hoursHelp = t('<p>In the practice hours section, do not report the training output of individual actives, but the actual practice hours organized for Helsinki residents belonging to the age group.<p><p>If the actual number of training hours for an age group cannot be ascertained (e.g. many mixed groups), can the share of that age group be calculated from the total number of hours based on the share of the number of active members from Helsinki in the age group out of the total number of active members from Helsinki. E.g. the total number of hours in the division (all mixed groups) is 100 hours, where 10 adults and 20 under 20-year-olds are active: the number of hours for adults is 33 hours and for under 20-year-olds 67 hours. However, the actual number of hours realized for the age group should be used primarily.</p>', [], $tOpts);

    $elements['adultHours'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Practice hours of adults (20-63 years)', [], $tOpts),
      '#help' => $hoursHelp,
      '#prefix' => '<div class="club-section__totalhours">',
      '#suffix' => '</div>',
      '#element_validate' => [
        [FieldValueValidator::class, 'validateAdultHours'],
      ],
    ];

    $elements['seniorMen'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Men from Helsinki (64 years and over)', [], $tOpts),
      '#prefix' => '<div class="club-section__participants">',
      '#element_validate' => [
        [FieldValueValidator::class, 'validate'],
      ],
    ];

    $elements['seniorWomen'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Women from Helsinki (64 years and over)', [], $tOpts),
      '#element_validate' => [
        [FieldValueValidator::class, 'validate'],
      ],
    ];

    $elements['seniorOthers'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Others from Helsinki (64 years and over)', [], $tOpts),
      '#suffix' => '</div>',
      '#element_validate' => [
        [FieldValueValidator::class, 'validate'],
      ],
    ];

    $elements['seniorHours'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Practice hours of adults (64 years and over)', [], $tOpts),
      '#help' => $hoursHelp,
      '#prefix' => '<div class="club-section__totalhours">',
      '#suffix' => '</div>',
      '#element_validate' => [
        [FieldValueValidator::class, 'validateSeniorHours'],
      ],
    ];

    $elements['boys'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Boys from Helsinki (under 20 years of age)', [], $tOpts),
      '#prefix' => '<div class="club-section__participants">',
      '#element_validate' => [
        [FieldValueValidator::class, 'validate'],
      ],
    ];

    $elements['girls'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Girls from Helsinki (under 20 years of age)', [], $tOpts),
      '#element_validate' => [
        [FieldValueValidator::class, 'validate'],
      ],
    ];

    $elements['juniorOthers'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Others from Helsinki (under 20 years of age)', [], $tOpts),
      '#suffix' => '</div>',
      '#element_validate' => [
        [FieldValueValidator::class, 'validate'],
      ],
    ];

    $elements['juniorHours'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Practice hours of children/young people (under 20 years of age)', [], $tOpts),
      '#help' => $hoursHelp,
      '#prefix' => '<div class="club-section__totalhours">',
      '#suffix' => '</div>',
      '#element_validate' => [
        [FieldValueValidator::class, 'validateJuniorHours'],
      ],
    ];

    return $elements;
  }

  /**
   * Get options for sectionName field.
   */
  private static function getOptions(): array {
    $tOpts = ['context' => 'grants_club_section'];

    return [
      t('Other combat sport', [], $tOpts),
      t('Other sport', [], $tOpts),
      t('Dog agility', [], $tOpts),
      t('Aikido', [], $tOpts),
      t('American football', [], $tOpts),
      t('Shooting sport', [], $tOpts),
      t('Biathlon', [], $tOpts),
      t('Auto racing', [], $tOpts),
      t('Baseball & softball', [], $tOpts),
      t('Cue sports', [], $tOpts),
      t('Brazilian jiu-jitsu', [], $tOpts),
      t('Bridge', [], $tOpts),
      t('Cheerleading', [], $tOpts),
      t('Curling', [], $tOpts),
      t('Darts', [], $tOpts),
      t('Esports', [], $tOpts),
      t('Fitness and figure competition', [], $tOpts),
      t('Disc golf', [], $tOpts),
      t('Golf', [], $tOpts),
      t('Skiing', [], $tOpts),
      t('Hockey', [], $tOpts),
      t('Air sports', [], $tOpts),
      t('Football & futsal', [], $tOpts),
      t('Archery', [], $tOpts),
      t('Judo', [], $tOpts),
      t('Ice hockey', [], $tOpts),
      t('Bandy', [], $tOpts),
      t('Karate', [], $tOpts),
      t('Rinkball', [], $tOpts),
      t('Bowling', [], $tOpts),
      t('Kendo sports', [], $tOpts),
      t('Climbing', [], $tOpts),
      t('Basketball', [], $tOpts),
      t('Cricket', [], $tOpts),
      t('Finnish skittles', [], $tOpts),
      t('Handball', [], $tOpts),
      t('Volleyball', [], $tOpts),
      t('Ultimate', [], $tOpts),
      t('Ice skating', [], $tOpts),
      t('Snowboarding', [], $tOpts),
      t('Paddling & rowing', [], $tOpts),
      t('Fencing & modern pentathlon', [], $tOpts),
      t('Motorsport', [], $tOpts),
      t('Boxing', [], $tOpts),
      t('Padel', [], $tOpts),
      t('Wrestling', [], $tOpts),
      t('Weightlifting', [], $tOpts),
      t('Parkour', [], $tOpts),
      t('Finnish baseball', [], $tOpts),
      t('Pétanque', [], $tOpts),
      t('Kickboxing', [], $tOpts),
      t('Sailing & boating', [], $tOpts),
      t('Cycling', [], $tOpts),
      t('Table tennis', [], $tOpts),
      t('Miniature golf', [], $tOpts),
      t('Horse riding', [], $tOpts),
      t('Harness racing', [], $tOpts),
      t('Ringette', [], $tOpts),
      t('Rugby football', [], $tOpts),
      t('Skateboarding', [], $tOpts),
      t('Floorball', [], $tOpts),
      t('Chess', [], $tOpts),
      t('Squash', [], $tOpts),
      t('Underwater diving', [], $tOpts),
      t('Badminton', [], $tOpts),
      t('Orienteering', [], $tOpts),
      t('Taekwondo', [], $tOpts),
      t('Figure skating', [], $tOpts),
      t('Dancesport', [], $tOpts),
      t('Tennis', [], $tOpts),
      t('Thai boxing', [], $tOpts),
      t('Darts sports', [], $tOpts),
      t('Triathlon', [], $tOpts),
      t('Swimming', [], $tOpts),
      t('Mushing', [], $tOpts),
      t('Mixed martial arts', [], $tOpts),
      t('Water skiing & wakeboarding', [], $tOpts),
      t('Strenght sports', [], $tOpts),
      t('Powerlifting', [], $tOpts),
      t('Gymnastics', [], $tOpts),
      t('Sport of athletics', [], $tOpts),
    ];
  }

}
