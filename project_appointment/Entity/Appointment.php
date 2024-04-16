<?php

namespace Drupal\project_appointment\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 *
 * @ingroup project_appointment
 *
 * @ContentEntityType(
 *   id = "appointment",
 *   label = @Translation("Appointment"),
 *   base_table = "project_appointment",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "appointment_date",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *   },
 * )
 */
class Appointment extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

 
    $fields['appointment_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(\Drupal::translation()->translate('Appointment Date and Time'))
      ->setDescription(\Drupal::translation()->translate('The date and time of the appointment.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    
  }

}
