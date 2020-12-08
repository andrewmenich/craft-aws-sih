<?php
/**
 * AWS SIH plugin for Craft CMS 3.x
 *
 * A simple plugin that transforms images using AWS' Serverless Image Handler CloudFormation stack.
 *
 * @link      https://github.com/andrewmenich
 * @copyright Copyright (c) 2020 Andrew Menich
 */

namespace andrewmenich\awssih\services;

use andrewmenich\awssih\AwsSih;

use Craft;
use craft\base\Component;

/**
 * Helpers Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Andrew Menich
 * @package   AwsSih
 * @since     1.0.0
 */
class Helpers extends Component
{
    // Public Methods
    // =========================================================================

  /**
   * Get focalpoint translation
   *
   * @param $focalPoint
   * @return string
   */
  public function getFocalPoint($focalPoint)
  {
    $position = [
      0 => [0 => "left top",      1 => "top",         2 => "right top"],
      1 => [0 => "left",          1 => "center",      2 => "right"],
      2 => [0 => "left bottom",   1 => "bottom",      2 => "right bottom"]
    ];

    $xCord = round($focalPoint['x'] * 2);
    $yCord = round($focalPoint['y'] * 2);

    return $position[$yCord][$xCord];
  }

  /**
   * Get WebP support
   *
   * @return bool
   */
  public function getClientWebPSupport(): bool
  {
    return Craft::$app->getRequest()->accepts('image/webp');
  }
}
