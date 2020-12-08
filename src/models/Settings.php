<?php
/**
 * AWS SIH plugin for Craft CMS 3.x
 *
 * A simple plugin that transforms images using AWS' Serverless Image Handler CloudFormation stack.
 *
 * @link      https://github.com/andrewmenich
 * @copyright Copyright (c) 2020 Andrew Menich
 */

namespace andrewmenich\awssih\models;

use Craft;
use craft\base\Model;

/**
 * AwsSih Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Andrew Menich
 * @package   AwsSih
 * @since     1.0.0
 *
 * @property-read string $secretKey
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Serverless distribution URL (the CloudFront Distribution URL generated by the AWS SIH CloudFormation stack)
     *
     * @var string
     */
    public $serverlessDistributionURL = '';

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
      return [
        ['serverlessDistributionURL', 'string'],
      ];
    }

    /**
     * Returns the parsed environment variable.
     *
     * @return string
     */
    public function getSecretKey(): string
    {
      return Craft::parseEnv($this->serverlessDistributionURL);
    }
}