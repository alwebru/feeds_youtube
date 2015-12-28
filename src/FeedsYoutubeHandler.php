<?php

/**
 * @file
 * Contains \Drupal\feeds_youtube\FeedsYoutubeHandler.
 */

namespace Drupal\feeds_youtube;

use Drupal\Core\Url;


class FeedsYoutubeHandler {

  public static function buildUrl(array $yt_state) {
    // https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=UCAJALvsCWz8Kh6wOySHUJAA&order=date&maxResults=50&key={api_key}

    $query = [
      'key' => $yt_state['api_key'],
      'part' => 'snippet',
      'channelId' => $yt_state['channel_id'],
      'order' => 'date',
      'maxResults' => $yt_state['page_limit'] == 0 ? 50 : $yt_state['page_limit'],
    ];

    if ($yt_state['pageToken']) {
      $query['pageToken'] = $yt_state['pageToken'];
    }

    return Url::fromUri('https://www.googleapis.com/youtube/v3/search', ['query' => $query])->toString();
  }

}
