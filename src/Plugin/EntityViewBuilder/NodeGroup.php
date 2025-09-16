<?php

namespace Drupal\server_og\Plugin\EntityViewBuilder;

use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgAccessInterface;
use Drupal\server_general\EntityViewBuilder\NodeViewBuilderAbstract;
use Drupal\server_general\SocialShareTrait;
use Drupal\server_general\ThemeTrait\TitleAndLabelsThemeTrait;
use Drupal\server_og\ThemeTrait\ElementNodeGroupThemeTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The "Node Group" PEVB plugin.
 *
 * @EntityViewBuilder(
 *   id = "node.group",
 *   label = @Translation("Node - Group"),
 *   description = "Node view builder for Group bundle."
 * )
 */
class NodeGroup extends NodeViewBuilderAbstract {

  use ElementNodeGroupThemeTrait;
  use SocialShareTrait;
  use TitleAndLabelsThemeTrait;

  /**
   * User proxy.
   *
   * @var AccountProxyInterface
   */
  public $currentUser;

  /**
   * OG membership serivice.
   *
   * @var MembershipManagerInterface
   */
  public MembershipManagerInterface $ogMembershipManager;

  /**
   * OF access service.
   *
   * @var OgAccessInterface
   */
  public OgAccessInterface $ogAccess;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->currentUser = $container->get('current_user');
    $plugin->ogMembershipManager = $container->get('og.membership_manager');
    $plugin->ogAccess = $container->get('og.access');

    return $plugin;
  }

  /**
   * Build full view mode.
   *
   * @param array $build
   *   The existing build.
   * @param NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(array $build, NodeInterface $entity) {
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle());
    $media = $entity->get('field_featured_image')->referencedEntities();

    $build[] = $this->buildElementNodeGroup(
      $entity->label(),
      $node_type->label(),
      $this->buildEntities($media, 'hero'),
      $this->buildProcessedText($entity),
      $this->buildSocialShare($entity),
      $this->getGroupMessage($entity),
    );

    return $build;
  }

  /**
   * Help function for build subscribe/unsubscribe link.
   *
   * @param NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   The message build array.
   */
  public function getGroupMessage(NodeInterface $entity): array {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'p-6',
          'md:px-8',
          'bg-cyan-500',
          'text-white',
        ],
      ],
      'content' => [
        '#markup' => $this->getTextLink($entity),
      ] + $this->getCacheContext($entity),
    ];
  }

  /**
   * Help function for getting link text.
   *
   * @param NodeInterface $entity
   *   The entity.
   *
   * @return string|false
   *   The link build array.
   */
  public function getTextLink(NodeInterface $entity): string|false {
    $user_membership = $this->ogMembershipManager->getMembership($entity, $this->currentUser->id());
    if ($user_membership && $user_membership->isOwner()) {
      return $this->t('You are the owner of this group and cannot unsubscribe.');
    }

    $user_pending = $this->ogMembershipManager->isMemberPending($entity, $this->currentUser->id());
    if ($user_pending) {
      return $this->t('Your request to join this group is awaiting moderator approval.');
    }

    $is_member = $this->ogMembershipManager->isMember($entity, $this->currentUser->id());
    $params = [
      'entity_type_id' => $entity->getEntityTypeId(),
      'group' => $entity->id(),
    ];
    $variables = [
      '@name' => $this->currentUser->getDisplayName(),
      '@label' => $entity->label(),
      '@link' => Link::fromTextAndUrl($this->t('click here'), Url::fromRoute('og.unsubscribe', $params))->toString(),
    ];
    if (!$is_member) {
      $is_can_subscribe = $this->ogAccess->userAccess($entity, 'subscribe', $this->currentUser)->isAllowed();
      if ($is_can_subscribe) {
        $variables['@link'] = Link::fromTextAndUrl($this->t('click here'), Url::fromRoute('og.subscribe', $params))->toString();
        return $this->t('Hi @name, <strong>@link</strong> if you would like to subscribe to this group called @label.', $variables);
      }
    }
    else {
      return $this->t('Hi @name, <strong>@link</strong> if you would like to unsubscribe from this group called @label.', $variables);
    }

    return FALSE;
  }

  /**
   * Help function for build cache context.
   *
   * @param NodeInterface $entity
   *   The entity.
   *
   * @return array
   *   The link build array.
   */
  public function getCacheContext(NodeInterface $entity): array {
    return [
      '#cache' => [
        'contexts' => [
          'user',
          'og_membership_state',
        ],
        'tags' => [
          'og_group:' . $entity->getEntityTypeId() . ':' . $entity->id(),
        ],
      ],
    ];
  }

}
