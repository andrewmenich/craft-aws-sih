<?php
/**
 * AWS SIH plugin for Craft CMS 3.x
 *
 * A simple plugin that transforms images using AWS' Serverless Image Handler CloudFormation stack.
 *
 * @link      https://github.com/andrewmenich
 * @copyright Copyright (c) 2020 Andrew Menich
 */

namespace andrewmenich\awssih\variables;

use andrewmenich\awssih\AwsSih;

use Craft;
use craft\elements\Asset;

/**
 * AWS SIH Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.awsSih }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Andrew Menich
 * @package   AwsSih
 * @since     1.0.0
 */
class AwsSihVariable
{


  // Public Methods
    // =========================================================================

  /**
   * Outputs a CloudFront URL (pointing at the SIH distribution) with Base64'd JSON that instructs AWS what transforms should be applied.
   * This variable is to provide legacy support for the previous plugin that this one is modelled after.
   *
   * @param Asset $image
   * @param array $edits
   * @return string
   * @throws \yii\base\InvalidConfigException
   */
  public function getImgUrl($image, array $edits = null, bool $allowWebP = true)
  {
    return AwsSih::$plugin->transform->legacyTransform($image, $edits, $allowWebP);
  }

  public function simpleResize($image, $width, $height): string
  {
    return AwsSih::$plugin->transform->simpleResize($image, $width, $height);
  }

  public function resize($image, array $edits = [])
  {
    return AwsSih::$plugin->transform->resize($image, $edits);
  }
}
