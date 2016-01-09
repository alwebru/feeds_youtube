<?php

/**
 * @file
 * Contains \Drupal\feeds_youtube\Feeds\Parser\YoutubeParser.
 */

namespace Drupal\feeds_youtube\Feeds\Parser;

use Drupal\feeds\Component\XmlParserTrait;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\Result\ParserResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\Component\Serialization\Json;
use Drupal\feeds_youtube\Feeds\Item\YoutubeItem;;
use GuzzleHttp\Exception\RequestException;
use Drupal\feeds_youtube\FeedsYoutubeHandler;

/**
 * Defines an Youtube parser using XPath.
 *
 * @FeedsParser(
 *   id = "youtube",
 *   title = @Translation("Youtube"),
 *   description = @Translation("Parses Youtube videos list with XPath.")
 * )
 */
class YoutubeParser extends PluginBase implements ParserInterface {
  use XmlParserTrait;

  // @todo remove this | just for dev purposes
  private $res = array();

  // Counter for the parsed items.
  private $items_count;

  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    // Set time zone to GMT for parsing dates with strtotime().
    $tz = date_default_timezone_get();
    date_default_timezone_set('GMT');

    // Reset item counter.
    $this->items_count = 0;

    // Get raw data.
    $raw = trim($fetcher_result->getRaw());
    if (!strlen($raw)) {
      throw new EmptyFeedException();
    }

    $data = Json::decode($raw);
    $result = new ParserResult();

    if ($data && count($data['items']) > 0) {
      $this->processItems($data['items'], $result);
    }

    if ($data['pageInfo']['totalResults'] && $data['pageInfo']['resultsPerPage'] && $data['pageInfo']['totalResults'] > $data['pageInfo']['resultsPerPage']) {
      $number_of_pages = $data['pageInfo']['totalResults'] / $data['pageInfo']['resultsPerPage'];
      if ($number_of_pages > 1 ) {
        $feed_type = $feed->getType();
        $fetcher_configuration = $feed_type->getFetcher()->getConfiguration();

        $yt_state = [
          'channel_id' => $feed->getSource(),
          'api_key' => $fetcher_configuration['api_key'],
          'import_limit' => $fetcher_configuration['import_limit'],
          'page_limit' => $fetcher_configuration['page_limit'],
          'pageToken' => '',
        ];

        for ($i=0;  $i <= $number_of_pages ; $i++) {
          if (!$data) {
            throw new EmptyFeedException();
          }
          if ($data['nextPageToken']) {
            $yt_state['pageToken'] = $data['nextPageToken'];
            $data = Json::decode($this->fetchInternal($feed, $yt_state));
            $this->processItems($data['items'], $result);
          }
        }
      }
    }

    date_default_timezone_set($tz);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [
      'video_id' => [
        'label' => $this->t('Video ID'),
        'description' => $this->t('YouTube video unique ID.'),
      ],
      'video_title' => [
        'label' => $this->t('Video title'),
        'description' => $this->t('Video title.'),
      ],
      'video_description' => [
        'label' => $this->t('Video description'),
        'description' => $this->t('Description of the video.'),
      ],
      'video_url' => [
        'label' => $this->t('Video URL'),
        'description' => $this->t('The URL of the video.'),
      ],
      'thumbnail_default' => [
        'label' => $this->t('Thumbnail (default)'),
        'description' => $this->t('The URL to default thumbnail of the video.'),
      ],
      'thumbnail_medium' => [
        'label' => $this->t('Thumbnail (medium)'),
        'description' => $this->t('The URL to medium size thumbnail of the video.'),
      ],
      'thumbnail_high' => [
        'label' => $this->t('Thumbnail (high)'),
        'description' => $this->t('The URL to high size thumbnail of the video.'),
      ],
      'published_datetime' => [
        'label' => $this->t('Published on (Datetime)'),
      ],
      'published_timestamp' => [
        'label' => $this->t('Published on (Timestamp)'),
      ],
      'channel_id' => [
        'label' => $this->t('Channel ID'),
        'description' => $this->t('YouTube channel ID.'),
      ],
      'channel_title' => [
        'label' => $this->t('Channel title'),
        'description' => $this->t('Channel title.'),
      ],
      'channel_url' => [
        'label' => $this->t('Channel URL'),
        'description' => $this->t('The URL of the channel.'),
      ],
      'category' => [
        'label' => $this->t('Category'),
      ],
      'tags' => [
        'label' => $this->t('Tags'),
        'description' => $this->t('This can be imported directly with Taxonomy "tags" vocabularies.'),
      ],
      // @todo remove this item property in future.
      'type' => [
        'label' => $this->t('Type'),
      ],
    ];
  }

  private function processItems($items, &$result) {
    foreach ($items as $yt_item) {
      if (!empty($yt_item['id']['kind']) && $yt_item['id']['kind'] == 'youtube#video') {
        $item = new YoutubeItem();

        if (!empty($yt_item['id']['videoId'])) {
          $item->set('video_id', $yt_item['id']['videoId']);
          $item->set('video_url', 'https://www.youtube.com/watch?v=' . $yt_item['id']['videoId']);
          // @todo remove this item property in future.
          $item->set('type', 'video');
        }

        if (!empty($yt_item['snippet']['title'])) {
          $item->set('video_title', $yt_item['snippet']['title']);
        }

        if (!empty($yt_item['snippet']['description'])) {
          $item->set('video_description', $yt_item['snippet']['description']);
        }

        if (!empty($yt_item['snippet']['channelId'])) {
          $item->set('channel_id', $yt_item['snippet']['channelId']);
          $item->set('channel_url', 'https://www.youtube.com/channel/' . $yt_item['snippet']['channelId']);
        }

        if (!empty($yt_item['snippet']['channelTitle'])) {
          $item->set('channel_title', $yt_item['snippet']['channelTitle']);
        }

        if (!empty($yt_item['snippet']['publishedAt'])) {
          $item->set('published_datetime', $yt_item['snippet']['channelTitle']);
          $item->set('published_timestamp', strtotime($yt_item['snippet']['channelTitle']));
        }

        if (!empty($yt_item['snippet']['thumbnails']['default']['url'])) {
          $item->set('thumbnail_default', $yt_item['snippet']['thumbnails']['default']['url']);
        }

        if (!empty($yt_item['snippet']['thumbnails']['medium']['url'])) {
          $item->set('thumbnail_medium', $yt_item['snippet']['thumbnails']['medium']['url']);
        }

        if (!empty($yt_item['snippet']['thumbnails']['high']['url'])) {
          $item->set('thumbnail_high', $yt_item['snippet']['thumbnails']['high']['url']);
        }

        // @todo Process categories.

        // @todo Process tags.

        $result->addItem($item);
        $this->res[] = $item;
        $this->items_count++;
      }
    }
  }

  private function fetchInternal(FeedInterface $feed, array $yt_state) {
    $url = FeedsYoutubeHandler::buildURL($yt_state);

    $response = $this->getInternal($url);
    $data = $response->getBody()->getContents();
    return $data;
  }

  /**
   * Performs GET requests for internal pages.
   * @param $url
   * @return \Psr\Http\Message\ResponseInterface
   */
  protected function getInternal($url) {
    $client = \Drupal::httpClient();

    try {
      $response = $client->get($url);
    }
    catch (RequestException $e) {
      watchdog_exception('feeds_youtube', $e->getMessage());
    }

    return $response;
  }

}
