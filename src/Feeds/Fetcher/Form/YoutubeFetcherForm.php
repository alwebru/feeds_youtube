<?php

/**
 * @file
 * Contains \Drupal\feeds_youtube\Feeds\Fetcher\Form\YoutubeFetcherForm.
 */

namespace Drupal\feeds_youtube\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * The configuration form for youtube fetchers.
 */
class YoutubeFetcherForm extends ExternalPluginFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Google API key.
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google API key'),
      '#description' => $this->t('Google API key.'),
      '#default_value' => $this->plugin->getConfiguration('api_key'),
    ];

    // Specify the limit of videos to import from youtube.
    $form['import_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('The limit of videos to  import'),
      '#description' => $this->t('Specify the limit of videos to import from youtube.'),
      '#default_value' => $this->plugin->getConfiguration('import_limit'),
      '#min' => 0,
    ];

    // Specify the limit of videos to import from youtube.
    $form['page_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit videos per API request'),
      '#description' => $this->t('Specify the limit of the number of retrieved video per API request.'),
      '#default_value' => $this->plugin->getConfiguration('page_limit'),
      '#min' => 0,
    ];

    // Per feed type override of global http request timeout setting.
    $form['request_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Request timeout'),
      '#description' => $this->t('Timeout in seconds to wait for an HTTP request to finish.'),
      '#default_value' => $this->plugin->getConfiguration('request_timeout'),
      '#min' => 0,
    ];

    return $form;
  }

}
