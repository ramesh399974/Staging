<?php

namespace app\modules\application\models;

use Yii;

class ApplicationBrandConsentStandards extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_brand_consent_form_standards';
    }

    /**
     * {@inheritdoc}
     */
    

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'brand_consent_form_id' => 'User ID',
            'app_id' => 'App ID',
            'standard_id' => 'Standard ID',
        ];
    }

}