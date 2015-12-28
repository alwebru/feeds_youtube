<?php

/**
 * @file
 * Contains \Drupal\feeds_youtube\Feeds\Fetcher\YoutubeFetcher.
 */

namespace Drupal\feeds_youtube\Feeds\Fetcher;

use Drupal\feeds\Feeds\Fetcher\HttpFetcher;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Result\HttpFetcherResult;
use Drupal\feeds_youtube\FeedsYoutubeHandler;
use Symfony\Component\HttpFoundation\Response;
use Drupal\feeds\Exception\EmptyFeedException;

/**
 * Defines an Youtube fetcher.
 *
 * @FeedsFetcher(
 *   id = "youtube",
 *   title = @Translation("Youtube"),
 *   description = @Translation("Downloads data from Youtube using Google API Client."),
 *   configuration_form = "Drupal\feeds_youtube\Feeds\Fetcher\Form\YoutubeFetcherForm",
 *   arguments = {"@http_client", "@cache.feeds_youtube", "@file_system"}
 * )
 */
class YoutubeFetcher extends HttpFetcher {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $sink = $this->generateSink();

    $configuration = $this->getConfiguration();
    $yt_state = [
      'channel_id' => $feed->getSource(),
      'api_key' => $configuration['api_key'],
      'import_limit' => $configuration['import_limit'],
      'page_limit' => $configuration['page_limit'],
      'pageToken' => '',
    ];

    $url = FeedsYoutubeHandler::buildURL($yt_state);

    $response = $this->get($url, $sink, $this->getCacheKey($feed));

    // 304, nothing to see here.
    if ($response->getStatusCode() == Response::HTTP_NOT_MODIFIED) {
      $state->setMessage($this->t('The feed has not been updated.'));
      throw new EmptyFeedException();
    }

    return new HttpFetcherResult($sink, $response->getHeaders());
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => '',
      'import_limit' => 0,
      'page_limit' => 0,
      'request_timeout' => 30,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildFeedForm(array $form, FormStateInterface $form_state, FeedInterface $feed) {
    $form['channel_id'] = [
      '#title' => $this->t('Channel ID'),
      '#type' => 'textfield',
      '#default_value' => $feed->getSource(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitFeedForm(array &$form, FormStateInterface $form_state, FeedInterface $feed) {
    $feed->setSource($form_state->getValue('channel_id'));
  }

  private function generateSink() {
    $sink = $this->fileSystem->tempnam('temporary://', 'feeds_youtube_fetcher');
    $sink = $this->fileSystem->realpath($sink);
    return $sink;
  }

}
