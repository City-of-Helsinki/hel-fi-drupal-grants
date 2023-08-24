<?php

namespace Drupal\grants_club_section\Element;

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
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];
    $tOpts = ['context' => 'club_section_composite'];

    $elements['sectionName'] = [
      '#type' => 'select',
      '#title' => t('Laji', [], $tOpts),
      '#options' => self::getOptions(),
    ];

    $elements['women'] = [
      '#type' => 'number',
      '#title' => t('Woman (20-63 years)'),
    ];

    $elements['men'] = [
      '#type' => 'number',
      '#title' => t('Men (20-63 years)'),
    ];

    $elements['adultOthers'] = [
      '#type' => 'number',
      '#title' => t('Others (20-63 years)'),
    ];

    $elements['adultHours'] = [
      '#type' => 'number',
      '#title' => t('Practice hours of adults (20-63 years)'),
    ];

    $elements['boys'] = [
      '#type' => 'number',
      '#title' => t('Boys (under 20 years of age)'),
    ];

    $elements['girls'] = [
      '#type' => 'number',
      '#title' => t('Girls (under 20 years of age)'),
    ];

    $elements['juniorOthers'] = [
      '#type' => 'number',
      '#title' => t('Others (under 20 years of age)'),
    ];

    $elements['juniorHours'] = [
      '#type' => 'number',
      '#title' => t('Practice hours of children/young people (under 20 years of age)'),
    ];

    return $elements;
  }

  /**
   * Get options for sectionName field.
   */
  private static function getOptions(): array {
    return [
      t('Dog agility'),
      t('Aikido'),
      t('American football'),
      t('Shooting sport'),
      t('Biathlon'),
      t('Auto racing'),
      t('Baseball & softball'),
      t('Cue sports'),
      t('Brazilian jiu-jitsu'),
      t('Bridge'),
      t('Cheerleading'),
      t('Curling'),
      t('Darts'),
      t('Esports'),
      t('Fitness and figure competition'),
      t('Disc golf'),
      t('Golf'),
      t('Skiing'),
      t('Hockey'),
      t('Air sports'),
      t('Football & futsal'),
      t('Archery'),
      t('Judo'),
      t('Ice hockey'),
      t('Bandy'),
      t('Karate'),
      t('Rinkball'),
      t('Bowling'),
      t('Kendo sports'),
      t('Climbing'),
      t('Basketball'),
      t('Cricket'),
      t('Finnish skittles'),
      t('Handball'),
      t('Volleyball'),
      t('Ultimate'),
      t('Ice skating'),
      t('Snowboarding'),
      t('Paddling & rowing'),
      t('Fencing & modern pentathlon'),
      t('Motorsport'),
      t('Boxing'),
      t('Padel'),
      t('Wrestling'),
      t('Weightlifting'),
      t('Parkour'),
      t('Finnish baseball'),
      t('Pétanque'),
      t('Kickboxing'),
      t('Sailing & boating'),
      t('Cycling'),
      t('Table tennis'),
      t('Miniature golf'),
      t('Horse riding'),
      t('Harness racing'),
      t('Ringette'),
      t('Rugby football'),
      t('Skateboarding'),
      t('Floorball'),
      t('Chess'),
      t('Squash'),
      t('Underwater diving'),
      t('Badminton'),
      t('Orienteering'),
      t('Taekwondo'),
      t('Figure skating'),
      t('Dancesport'),
      t('Tennis'),
      t('Thai boxing'),
      t('Darts sports'),
      t('Triathlon'),
      t('Swimming'),
      t('Mushing'),
      t('Mixed martial arts'),
      t('Water skiing & wakeboarding'),
      t('Strenght sports'),
      t('Powerlifting'),
      t('Gymnastics'),
      t('Sport of athletics'),
      t('Other combat sport'),
      t('Other sport'),
    ];
  }

}
