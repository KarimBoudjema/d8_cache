<?php

namespace Drupal\d8_cache\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'CacheBlock' block without caching.
 *
 * @Block(
 *  id = "cache_block",
 *  admin_label = @Translation("Cache block"),
 * )
 */
class CacheBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Real time: @time',
            ['@time' => date("H:i:s")]),

      '#cache' => [
        'max-age' => 0,
      ],

    ];
  }

}
