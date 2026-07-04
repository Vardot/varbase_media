<?php

declare(strict_types=1);

namespace Drupal\varbase_media_twitter\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_browser_generic_embed\OverrideHelper;
use Drupal\varbase_media_twitter\Plugin\media\Source\VarbaseMediaTwitter;

/**
 * Hook implementations for the VarbaseMediaTwitterHooks module.
 */
class VarbaseMediaTwitterHooks {

  /**
   * Constructs a VarbaseMediaTwitterHooks object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
  ) {}

  /**
   * Implements hook_media_source_info_alter().
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources): void {
    $sources['twitter']['input_match'] = [
      'constraint' => 'TweetEmbedCode',
      'field_types' => [
        'string',
        'string_long',
      ],
    ];
    $sources['twitter']['preview'] = TRUE;

    OverrideHelper::pluginClass($sources['twitter'], VarbaseMediaTwitter::class);
  }

  /**
   * Implements hook_form_FORM_ID_alter() for entity_embed_dialog.
   */
  #[Hook('form_entity_embed_dialog_alter')]
  public function formEntityEmbedDialogAlter(&$form, FormStateInterface $form_state, $form_id): void {
    // Only at the embed step.
    if ($form_state->get('step') == 'embed') {

      // Get the entity values and attributes.
      $entity_element = [];
      $entity_element += $form_state->get('entity_element');
      $form_state->set('entity_element', $entity_element);
      $entity = $form_state->get('entity');

      // Get the entity bundle type.
      $bundle_type = $entity->bundle();

      if ($bundle_type == "tweet") {
        $builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());

        // Render the Embed entity.
        $form['entity'] = [
          '#type' => 'item',
          '#markup' => $this->renderer->renderRoot($builder->view($entity, 's06')),
        ];

        if (isset($form['attributes']['data-align'])) {
          unset($form['attributes']['data-align']);
        }

        if (isset($form['attributes']['data-entity-embed-display-settings'])) {
          unset($form['attributes']['data-entity-embed-display-settings']);
        }

        if (isset($form['attributes']['data-caption'])) {
          unset($form['attributes']['data-caption']);
        }

        if (isset($form['attributes']['data-entity-embed-display'])) {
          $form['attributes']['data-entity-embed-display']['#access'] = FALSE;
          $form['attributes']['data-entity-embed-display']['#default_value'] = 'view_mode:media.original';
        }
      }
    }
  }

}
