<?php

namespace Drupal\Tests\server_og;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\OgMembershipInterface;
use Drupal\Tests\server_general\ExistingSite\ServerGeneralNodeTestBase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test 'group' content type (subscribe/unsubscribe group).
 */
class ServerGeneralNodeGroupTest extends ServerGeneralNodeTestBase {

  /**
   * @var NodeInterface $node
   */
  protected NodeInterface $node;

  /**
   * @var UserInterface $author
   */
  protected UserInterface $author;

  /**
   * @var UserInterface $user
   */
  protected UserInterface $user;

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle(): string {
    return 'group';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredFields(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionalFields(): array {
    return [
      'field_featured_image',
    ];
  }

  /**
   * Creates and logs in a user.
   */
  protected function createAndLoginUser(array $permissions = ['access content']): void {
    $user = $this->createUser($permissions);
    $this->drupalLogin($user);
    $this->user = $user;
  }

  /**
   * Setup basic data for tests.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->author = $this->createUser(['access content']);
    $this->drupalLogin($this->author);

    $node = $this->createNode([
      'title' => 'Group test node',
      'type' => $this->getEntityBundle(),
      'uid' => $this->author->id(),
      'field_body' => [
        'value' => 'This is the text of the body field.',
        'format' => 'full_html',
      ],
      'field_featured_image' => ['target_id' => 1],
      'moderation_state' => 'published',
    ]);
    $node->save();
    $this->node = $node;
  }

  /**
   * Check node available.
   */
  public function testNodeAvailable() {
    $this->drupalLogin($this->author);
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);
  }

  /**
   * Check node available for owner.
   */
  public function testNodeAvailableForOwner() {
    $this->drupalLogin($this->author);

    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->pageTextContains('You are the owner');
  }

  /**
   * Check node available for not owner.
   */
  public function testNodeAvailableForNotOwner() {
    $this->createAndLoginUser();

    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->pageTextContains('if you would like to subscribe to this group called');
  }

  /**
   * Check node available to subscribe.
   */
  public function testNodeAvailableToSubscribe() {
    $this->createAndLoginUser();

    $this->drupalGet(Url::fromRoute('og.subscribe', [
      'entity_type_id' => $this->node->getEntityTypeId(),
      'group' => $this->node->id(),
    ]));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->assertSession()->pageTextContains('Explain the motivation for your request to join this group');
    $this->assertSession()->fieldExists('og_membership_request[0][value]');

    $this->submitForm(['og_membership_request[0][value]' => 'Some reason for subscribe'], 'Request membership');
    $this->assertSession()->addressEquals($this->node->toUrl()->toString());
    $this->assertSession()->pageTextContains('Your request to join this group is awaiting moderator approval');
  }

  /**
   * Check node available to unsubscribe.
   */
  public function testNodeAvailableToUnsubscribe() {
    $this->createAndLoginUser();

    $membership = OgMembership::create([
      'type' => OgMembershipInterface::TYPE_DEFAULT,
      'entity_type' => $this->node->getEntityTypeId(),
      'entity_id' => $this->node->id(),
      'uid' => $this->user->id(),
      'state' => OgMembershipInterface::STATE_ACTIVE,
    ]);
    $membership->save();

    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->pageTextContains('if you would like to unsubscribe from this group called');

    $this->drupalGet(Url::fromRoute('og.unsubscribe', [
      'entity_type_id' => $this->node->getEntityTypeId(),
      'group' => $this->node->id(),
    ]));
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->assertSession()->pageTextContains('Unsubscribe');
    $this->submitForm([], 'Unsubscribe');

    $this->assertSession()->addressEquals($this->node->toUrl()->toString());
    $this->assertSession()->pageTextContains('if you would like to subscribe to this group called');
  }

}
