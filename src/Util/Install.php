<?php

namespace Drupal\server_og\Util;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\og\GroupTypeManager;

/**
 * Constructor of helper installer.
 */
class Install {

  /**
   * Create node type.
   *
   * @return void
   */
  public static function createNodeType(): void {
    if (!NodeType::load('group')) {
      $type = NodeType::create([
        'type' => 'group',
        'name' => 'Group',
        'description' => 'Use <em>Group</em> for your configurable pages containing group content.',
      ]);
      $type->save();
    }
  }

  /**
   * Create node fields.
   *
   * @return void
   */
  public static function createNodeFields(): void {
    if (!FieldConfig::loadByName('node', 'group', 'field_featured_image')) {
      FieldConfig::create([
        'field_name' => 'field_featured_image',
        'entity_type' => 'node',
        'bundle' => 'group',
        'label' => 'Image',
        'required' => FALSE,
        'settings' => [
          'alt_field' => TRUE,
          'alt_field_required' => FALSE,
          'title_field' => FALSE,
          'title_field_required' => FALSE,
        ],
        'translatable' => FALSE,
      ])->save();
    }

    if (!FieldConfig::loadByName('node', 'group', 'field_body')) {
      FieldConfig::create([
        'field_name' => 'field_body',
        'entity_type' => 'node',
        'bundle' => 'group',
        'label' => 'Body',
        'required' => FALSE,
        'settings' => [
          'display_summary' => TRUE,
        ],
        'translatable' => TRUE,
      ])->save();
    }
  }

  /**
   * Set node form display.
   *
   * @return void
   */
  public static function setNodeFormDisplay(): void {
    $form_display = EntityFormDisplay::load('node.group.default');
    if (!$form_display) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => 'group',
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    $form_display->setComponent('field_featured_image', [
      'type' => 'media_library_widget',
      'weight' => 1,
      'settings' => [],
    ]);

    $form_display->setComponent('field_body', [
      'type' => 'text_textarea_with_summary',
      'weight' => 2,
      'settings' => [],
    ]);

    $form_display->save();
  }

  /**
   * Set node view display.
   *
   * @return void
   */
  public static function setNodeViewDisplay(): void {
    $view_display = EntityViewDisplay::load('node.group.default');
    if (!$view_display) {
      $view_display = EntityViewDisplay::create([
        'targetEntityType' => 'node',
        'bundle' => 'group',
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    $view_display->setComponent('field_featured_image', [
      'type' => 'entity_reference_label',
      'label' => 'hidden',
      'weight' => 1,
      'settings' => [],
    ]);

    $view_display->setComponent('field_body', [
      'type' => 'text_default',
      'label' => 'hidden',
      'weight' => 2,
      'settings' => [],
    ]);

    $view_display->save();
  }

  /**
   * Create node type.
   *
   * @return void
   */
  public static function setContentTypeGroup(): void {
    if (NodeType::load('group')) {
      \Drupal::service('og.group_type_manager')->addGroup('node', 'group');
    }
  }


}
