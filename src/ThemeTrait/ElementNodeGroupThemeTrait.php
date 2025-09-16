<?php

namespace Drupal\server_og\ThemeTrait;

use Drupal\server_general\ThemeTrait\ElementLayoutThemeTrait;
use Drupal\server_general\ThemeTrait\Enum\WidthEnum;
use Drupal\server_general\ThemeTrait\InnerElementLayoutThemeTrait;
use Drupal\server_general\ThemeTrait\SocialShareThemeTrait;
use Drupal\server_general\ThemeTrait\TitleAndLabelsThemeTrait;

/**
 * Helper method for building the Node news element.
 */
trait ElementNodeGroupThemeTrait {

  use ElementLayoutThemeTrait;
  use InnerElementLayoutThemeTrait;
  use SocialShareThemeTrait;
  use TitleAndLabelsThemeTrait;

  /**
   * Build the Group news element.
   *
   * @param string $title
   *   The node title.
   * @param string $label
   *   The label of content type.
   * @param array $image
   *   The responsive image render array.
   * @param array $body
   *   The body render array.
   * @param array $social_share
   *   The render array of the Social share buttons.
   * @param array $og_message
   *   The render array of the subscribe message.
   *
   * @return array
   *   The render array.
   *
   * @throws \IntlException
   */
  protected function buildElementNodeGroup(
    string $title,
    string $label,
    array $image,
    array $body,
    array $social_share,
    array $og_message = [],
  ): array {
    $elements = [];

    // Header.
    $element = $this->buildGroupHeader(
      $title,
      $label,
    );
    $elements[] = $this->wrapContainerWide($element);

    // Main content and sidebar.
    $body = $this->wrapProseText($body);
    $element = $this->buildGroupMainAndSidebar(
      $image,
      $body,
      $social_share,
      $og_message,
    );
    $elements[] = $this->wrapContainerWide($element);

    $elements = $this->wrapContainerVerticalSpacingBig($elements);
    return $this->wrapContainerBottomPadding($elements);
  }

  /**
   * Build the header.
   *
   * @param string $title
   *   The node title.
   * @param string $label
   *   The label of content type.
   *
   * @return array
   *   Render array.
   */
  private function buildGroupHeader(string $title, string $label): array {
    $elements = [
      $this->buildPageTitle($title),
      $this->buildLabelsFromText([$label]),
    ];
    $elements = $this->wrapContainerVerticalSpacing($elements);

    return $this->wrapContainerMaxWidth($elements, WidthEnum::ThreeXl);
  }

  /**
   * Build the Main content and the sidebar.
   *
   * @param array $image
   *   The responsive image render array.
   * @param array $body
   *   The body render array.
   * @param array $social_share
   *   The render array of the Social share buttons.
   * @param array $og_message
   *   The render array of the subscribe message.
   *
   * @return array
   *   Render array
   */
  private function buildGroupMainAndSidebar(
    array $image,
    array $body,
    array $social_share,
    array $og_message = [],
  ): array {
    $main_elements = [];
    $sidebar_elements = [];

    if ($og_message) {
      $main_elements[] = $og_message;
    }

    $main_elements[] = $image;
    $main_elements[] = $body;

    $sidebar_elements[] = $social_share;
    $sidebar_elements = $this->wrapContainerVerticalSpacing($sidebar_elements);

    return $this->buildElementLayoutMainAndSidebar(
      $this->wrapContainerVerticalSpacingBig($main_elements),
      $this->buildInnerElementLayout($sidebar_elements),
    );
  }

}
