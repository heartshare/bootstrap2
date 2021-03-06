<?php

namespace app\models;

use app\components\BaseActive;
use Yii;

/**
 * This is the model class for component "Settings".
 *
 * @property string $key
 * @property string $value
 */
class Settings extends \yii\base\Model
{
    public $emailMain;
    public $emailName;
    public $emailPrefix;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['emailMain', 'trim'],
            ['emailMain', 'email'],
            
            ['emailName', 'trim'],
            ['emailName', 'string', 'max' => 255],

            ['emailPrefix', 'trim'],
            ['emailPrefix', 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'emailMain' => Yii::t('app', 'Primary email'),
            'emailName' => Yii::t('app', 'Sender name'),
            'emailPrefix' => Yii::t('app', 'Prefix'),
        ];
    }
}
