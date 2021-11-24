<?php

namespace app\modules\application\models;

use Yii;

class ApplicationBrandConsentForm extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_brand_consent_form';
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
            'user_id' => 'User ID',
            'app_id' => 'App ID',
            'brand_buyer_name' => 'Buyer Name',
            'accept_declaration	' => 'Accept Declaration',
            'authorized_person' => 'Authorized Person',
            'position' => 'Position',
            'date' => 'Date',
            'created_by	' => 'Created By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
           
        ];
    }

}