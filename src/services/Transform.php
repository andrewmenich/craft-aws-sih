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
use craft\elements\Asset;

/**
 * Transform Service
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
class Transform extends Component
{

    // Public Properties
    // =========================================================================

    /**
     * Plugins' SIH distribution URL.
     *
     * @var string
     */
    public $distributionSettingUrl;

    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
      parent::__construct($config);
      $this->distributionSettingUrl = Craft::parseEnv(AwsSih::$plugin->settings->serverlessDistributionURL);
    }

  /**
   *
   *
   * From any other plugin file, call it like this:
   *
   *     AwsSih::$plugin->transform->legacyTransform()
   *
   * @param $image
   * @param $edits
   * @param $allowWebP
   * @return string
   */
    public function legacyTransform($image, $edits, $allowWebP): string
    {
      $transformer = function($image, $edits, $allowWebP){

        $distributionUrl = $this->_getDistributionUrl($image);

        $json = $this->_getBaseJson($image, $edits);

        if (isset($edits['height'])) {
          $json["edits"]["resize"]["height"] = $edits['height'];
        }

        if (isset($edits['flip'])) {
          $json["edits"]["flip"] = $edits['flip'];
        }

        if (isset($edits['flop'])) {
          $json["edits"]["flop"] = $edits['flop'];
        }

        if (isset($edits['greyscale'])) {
          $json["edits"]["greyscale"] = $edits['greyscale'];
        }

        if (isset($edits['rotate'])) {
          $json["edits"]["rotate"] = $edits['rotate'];
        }

        if (isset($edits['blur']) && $edits['blur'] >= 0.3 && $edits['blur'] <= 1000) {
          $json["edits"]["blur"] = $edits['blur'];
        }

        // If client had webp support request webp version
        if ($allowWebP && AwsSih::$plugin->getHelpers()->getClientWebPSupport()) {
          $json["edits"]["webp"] = [];
        }

        return $distributionUrl . base64_encode(json_encode($json));
      };

      return $this->_preflightCheck($image, $transformer($image, $edits, $allowWebP));
    }

  /**
   *
   *
   *
   * @param $image
   * @param $width
   * @param $height
   * @return string
   */
  public function simpleResize($image, $width, $height): string
  {

    $resizer = function($image, $width, $height){
      $distributionUrl = $this->_getDistributionUrl($image);
      $edits = [
        "height" => $height,
        "width" => $width
      ];
      $json = $this->_getBaseJson($image, $edits);

      $json["edits"]["resize"] = array_merge($json["edits"]["resize"], $edits);
      return $distributionUrl . base64_encode(json_encode($json));
    };

    return $this->_preflightCheck($image, $resizer($image, $width, $height));
  }

  /**
   *
   *
   * From any other plugin file, call it like this:
   *
   *     AwsSih::$plugin->transform->legacyTransform()
   *
   * @param $image
   * @param $edits
   * @return string
   */
  public function resize($image, $edits): string
  {

    $resizer = function($image, $edits){
      $distributionUrl = $this->_getDistributionUrl($image);
      $json = $this->_getBaseJson($image, $edits);

      // We want to allow either height or width to be set as a ratio, e.g. 4/3
      if(isset($edits["width"], $edits["height"])){

        // is width set to a ratio?
        if(is_float($edits["width"])){
          $ratio = $edits["width"];
          $edits["width"] = $edits["height"] / $ratio;
        }

        // is height set to a ratio?
        if(is_float($edits["height"])){
          $ratio = $edits["height"];
          $edits["height"] = $edits["width"] / $ratio;
        }
      }

      $json["edits"]["resize"] = array_merge($json["edits"]["resize"], $edits);
      return $distributionUrl . base64_encode(json_encode($json));
    };

    return $this->_preflightCheck($image, $resizer($image, $edits));
  }

  // Private Methods
  // =========================================================================

  /**
   * First check to ensure that the provided image is an Asset element.
   * If so, continue creating Base64 encoded JSON that can be parsed by AWS
   * else, output an error message to the logs and return the input string (if provided) or an empty string
   * This is helpful for giving users options to run a regular URL string through the plugin for testing/dev purposes
   * and it also ensures that the plugin fails gracefully instead of bringing down the whole page
   *
   *
   *
   * @return string
   */
  private function _preflightCheck($image, $successCallback){

    if(is_a($image, Asset::class)) {

      if ($image->getVolume()->displayName() !== "Amazon S3") {
        return $image->url;
      }

      return $successCallback;
    } else{
      $type = is_object($image) ? get_class($image) : gettype($image);
      $assetType = Asset::class;
      Craft::error("The image was not transformed because the supplied image needs to be of the type: ${assetType}; ${type} provided.", __METHOD__);
      return $type === 'string' ? $image : '';
    }

  }

  /**
   *
   * @param $image
   * @param $edits
   * @return array
   */
  private function _getBaseJson($image, $edits): array
  {
    $volumeSubfolder = Craft::parseEnv($image->getVolume()->subfolder);

    $json = [
      "bucket" => $image->getVolume()->bucket,
      "key" => $volumeSubfolder ? $volumeSubfolder . "/" . $image->getPath() : $image->getPath(),
      "edits" => [
        "resize" => [
          "fit" => ($edits['fit'] ?? "cover"),
          "position" => ($edits['position'] ?? AwsSih::$plugin->getHelpers()->getFocalPoint($image->getFocalPoint())),
          "width" => $edits['width'] ?? 1980 // TODO: make 1980px plugin setting
        ]
      ]
    ];

    return $json;
  }

  private function _getDistributionUrl($image): string
  {
    $volumeUrl = Craft::parseEnv($image->volume->url);
    return $this->distributionSettingUrl ?: $volumeUrl;
  }
}
