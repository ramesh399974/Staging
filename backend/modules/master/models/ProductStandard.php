<?php

namespace app\modules\master\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class ProductStandard extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_product_standard';
    }

    
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Standard ID',
            'standard_id' => 'Product ID'
        ];
    }
    public function getStandard()
    {
        return $this->hasOne(Standard::className(), ['id' => 'standard_id']);
    }
}
