<?php

namespace Drupal\d8_cache\Controller;

// For the controller.
use Drupal\Core\Controller\ControllerBase;

// For the user entity and current user.
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

// To display messages.
use Drupal\Core\Messenger\MessengerInterface;

// For DI.
use Symfony\Component\DependencyInjection\ContainerInterface;

// For the cache.
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;


/**
 * Class CacheController.
 */
class CacheController extends ControllerBase {

  private $account;
  private $storage;
  protected $messenger;

  /**
   * CacheController constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user account.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $storage
   *   User account.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messages service.
   */
  public function __construct(
        AccountProxyInterface $account,
        EntityTypeManagerInterface $storage,
        MessengerInterface $messenger) {

    $this->account = $account;
    $this->storage = $storage;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('current_user'),
          $container->get('entity_type.manager'),
          $container->get('messenger')
      );
  }

  /**
   * Method to simulate a long process.
   *
   * @param int|null $wait
   *   Time to wait.
   * @param string|null $string
   *   String to display.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Markup to return.
   */
  public function getMyData(int $wait = NULL, string $string = NULL) {

    // Large process - Sleep.
    sleep($wait);

    // Return the final data of the process.
    $data = $this->t($string . ' @time',
        ['@time' => date("H:i:s")]);

    return $data;
  }

  /**
   * Method to explain the use of several cache methods.
   *
   * Explain the use of cache()->set(),
   * and cache()->get(), and Cache::invalidateTags($tags).
   *
   * We'll use this kind of code when we won't use any
   * metadata cacheability.
   *
   * @return array
   *   Render array.
   */
  public function cacheCode() {

    // Define variables.
    $myData = &drupal_static(__FUNCTION__);
    $cid = "d8_cache:code";
    $expire = CacheBackendInterface::CACHE_PERMANENT;
    $tags = ['d8_cache:my-tag'];

    // Check if already in the cache.
    if ($cache = \Drupal::cache()->get($cid)) {
      $this->messenger->addMessage(t('In cache'), 'status');
      $myData = $cache->data;
    }
    else {
      // If not in the cache, do the process.
      $this->messenger->addMessage(t('Not in cache'), 'warning');
      // Long process.
      $myData = $this->getMyData(2, "Time 10 seconds.");
      // Set into the default Cache.
      \Drupal::cache()->set($cid, $myData, $expire, $tags);
    }

    // Example to invalidate all cache items with certain tags.
    // \Drupal\Core\Cache\Cache::invalidateTags($tags);
    return [
      '#type' => 'markup',
      '#markup' => $myData,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * InternalNoCache.
   *
   * No page cache for anonymous users even if Internal Page Cache is
   * enabled through the page_cache_kill_switch service.
   *
   * @return array
   *   Render array.
   */
  public function internalNoCache() {

    // Don't cache this page with page_cache_kill_switch.
    \Drupal::service('page_cache_kill_switch')->trigger();

    // Simulate a long process.
    $data = $this->getMyData(2, "Internal Page Cache (Kill)");

    // Return the render array.
    return [
      '#type' => 'markup',
      '#markup' => $data,
      '#cache' => [
        'max-age' => 10,
      ],
    ];
  }

  /**
   * No cache in route.
   *
   * No page cache for anonymous users even if Internal Page Cache is
   * enabled through the option no-cache in the route.
   *
   * @return array
   *   Render array.
   */
  public function internalNoCacheRoute() {
    // No cache from route
    // Simulate a long process.
    $data = $this->getMyData(2, "Internal Page Cache (no-cache in route)");

    // Return the render array.
    return [
      '#type' => 'markup',
      '#markup' => $data,
    ];
  }

  /**
   * Illustrate max-age adding the metadata manually to the render array.
   *
   * @return array
   *   Render array.
   */
  public function cacheMaxAge() {

    // Simulate a long process.
    $data = $this->getMyData(2, "Max Age 10 seconds.");

    // Build the render array with cache metadata.
    $build = [
      '#type' => 'markup',
      '#markup' => $data,
      '#cache' => [
        'max-age' => 10,
      ],
    ];

    return $build;

  }

  /**
   * Adding metadata with CacheableMetadata class.
   *
   * Illustrate max-age adding the metadata with the
   * Drupal\Core\Cache\CacheableMetadata class.
   *
   * @return array
   *   Render Array.
   */
  public function cacheMaxAgeCode() {

    // Simulate a long process.
    $data = $this->getMyData(2, "Max Age 10 seconds with CacheableMetadata class.");

    // Build the render array.
    $build = [
      '#type' => 'markup',
      '#markup' => $data,
    ];

    // Add metadata to the render array.
    $cacheMetadata = new CacheableMetadata();
    $cacheMetadata->setCacheMaxAge(10);
    $cacheMetadata->applyTo($build);

    // Return the array.
    return $build;
  }

  /**
   * Illustrate Cache::PERMANENT.
   *
   * @return array
   *   Render Array.
   */
  public function cacheMaxAgePermanent() {
    // Simulate a long process.
    $data = $this->getMyData(2, "Cache permanent");

    // Return the render array.
    return [
      '#type' => 'markup',
      '#markup' => $data,
      '#cache' => [
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  /**
   * Cache Context by any query arguments in the URL.
   *
   * @return array
   *   Render Array.
   */
  public function cacheContextsByUrl() {
    // Simulate a long process.
    $data = $this->getMyData(2, "Context query_args");

    // Return the render array.
    return [
      '#type' => 'markup',
      '#markup' => $data,
      '#cache' => [
        'contexts' => ['url.query_args'],
      ],
    ];
  }

  /**
   * Cache Context by if the variable var is in the query arguments.
   *
   * @return array
   *   Render Array.
   */
  public function cacheContextsByUrlParam() {
    // Simulate a long process.
    $data = $this->getMyData(1, "Context query_args if variable var in query");
    // Return the render array.
    return [
      '#type' => 'markup',
      '#markup' => $data,
      '#cache' => [
        'contexts' => ['url.query_args:var'],
        'max-age' => 30,
      ],
    ];
  }

  /**
   * Cache tags invalidate if node 1 changes.
   *
   * @return array
   *   Render Array.
   */
  public function cacheTagsNode() {
    // Simulate a long process.
    $data = $this->getMyData(1, "Cache tags Node 1");
    // Return the render array.
    return [
      '#type' => 'markup',
      '#markup' => $data,
      '#cache' => [
        'tags' => ['node:1'],
      ],
    ];
  }

  /**
   * Illustrate the use of cache tags.
   *
   * In this example we get the cache tags from the current user entity
   * with the getCacheTags() method and place these tags in our render
   * array.
   *
   * Every time the information about this user will change, the cache
   * of our render array will be invalided.
   *
   * @return array
   *   Render Array.
   */
  public function cacheTags() {

    // Simulate a long process.
    $data = $this->getMyData(1, "Cache tags for current user. ");

    // Get the user ID trough the current user service.
    $userId = $this->account->id();

    // Load the user entity to retrieve its cache tags.
    try {
      $user = $this->storage->getStorage('user')->load($userId);
    }
    catch (\Exception $exception) {
      $this->messenger->addMessage(
        t('An error occurred and processing did not complete.'), 'error');
      return [];
    }

    // Get all the cache tags of this user.
    $cacheTags = $user->getCacheTags();

    // Get the name of the user.
    $userName = $user->getAccountName();

    // Transform array of tags in a string.
    $arrayString = '';
    if (is_array($cacheTags)) {
      $arrayString = implode(" ", $cacheTags);
    }

    // Create the final data to display.
    $finalData = $data . '<br />' .
                $this->t('Bonjour user: @user <br /> Votre Tag: @tags',
                ['@user' => $userName, '@tags' => $arrayString]);

    // Create the render array with the cache tags of the current user.
    $build = [
      '#type' => 'markup',
      '#markup' => $finalData,
      '#cache' => [
        'tags' => $cacheTags,
      ],
    ];

    return $build;
  }

  /**
   * Example of cache bubbling.
   *
   * The parent will receive the cache metadata from its children.
   *
   * @return array
   *   Render Array.
   */
  public function cacheTreeParent() {

    // Create the return array with metadata max-age permanent for the parent
    // But it will receive a max-age 10 form its child.
    $build = [
      'Parent' => [
        '#markup' => $this->t('PARENT: Il est: @time <br />',
          ['@time' => date('H:i:s')]),
        '#cache' => [
          'max-age' => Cache::PERMANENT,
          'keys' => ['d8_cache_permanent1'],
        ],
        'child-1' => [
          '#markup' => $this->t('CHILD: Il est: @time <br />',
            ['@time' => date('H:i:s')]),
          '#cache' => [
            'max-age' => 10,
            'keys' => ['d8_cache_child1'],
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Sub-arrays with no keys cache metadata.
   *
   * In this case, if one of the sub-array is invalided, all the caches
   * of other sub-arrays will be invalidated, event the permanent one because
   * they are at the same level without any keys to differentiate them.
   *
   * @return array
   *   Render Array.
   */
  public function cacheNoKeys() {

    $build = [
      'permanent' => [
        '#markup' => $this->t('PERMANENT: Super cette formation. @time <br />',
          ['@time' => date('H:i:s')]),
        '#cache' => [
          'max-age' => Cache::PERMANENT,

        ],
      ],
      'max-age' => [
        '#markup' => $this->t('MAX-AGE 10: Il est: @time <br />',
          ['@time' => date('H:i:s')]),
        '#cache' => [
          'max-age' => 10,
        ],
      ],
      'context-url' => [
        '#markup' => $this->t('CONTEXT URL: Il est: @time <br />',
          ['@time' => date('H:i:s')]),
        '#cache' => [
          'contexts' => ['url.query_args'],
        ],
      ],

    ];
    return $build;
  }

  /**
   * Avoid bubbling up of the cache adding 'keys'.
   *
   * @return array
   *   Render Array.
   */
  public function cacheKeys() {
    // Using keys to avoid bubbling up cache.
    $build = [
      'permanent' => [
        '#markup' => $this->t('PERMANENT: Super cette formation. @time <br />',
          ['@time' => date('H:i:s')]),
        '#cache' => [
          'max-age' => Cache::PERMANENT,
          'keys' => ['d8_cache_permanent'],
        ],
      ],
      'max-age' => [
        '#markup' => $this->t('MAX-AGE 10: Il est: @time <br />',
          ['@time' => date('H:i:s')]),
        '#cache' => [
          'max-age' => 10,
          'keys' => ['d8_cache_maxage'],
        ],
      ],
      'context-url' => [
        '#markup' => $this->t('CONTEXT URL: Il est: @time <br />',
          ['@time' => date('H:i:s')]),
        '#cache' => [
          'contexts' => ['url.query_args'],
          'keys' => ['d8_cache_contexts'],
        ],
      ],

    ];
    // Return the render array.
    return $build;
  }

}
