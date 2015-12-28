<?php

/**
 * @file
 * Contains \Drupal\feeds_youtube\Feeds\Item\YoutubeItem.
 */

namespace Drupal\feeds_youtube\Feeds\Item;

use Drupal\feeds\Feeds\Item\BaseItem;

/**
 * Defines an item class for use with an Youtube parser.
 */
class YoutubeItem extends BaseItem {

  protected $video_id;
  protected $video_title;
  protected $video_description;
  protected $video_url;
  protected $thumbnail_default;
  protected $thumbnail_medium;
  protected $thumbnail_high;
  protected $published_datetime;
  protected $published_timestamp;
  protected $channel_id;
  protected $channel_title;
  protected $category;
  protected $tags;

}
