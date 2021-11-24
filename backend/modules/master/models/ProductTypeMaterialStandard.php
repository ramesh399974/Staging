<?php

namespace app\modules\master\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class ProductTypeMaterialStandard extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_product_type_material_standard';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_type_material_id', 'standard_id'], 'integer']
        ];
    }

    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_type_material_id' => 'Product Type Material ID',
            'standard_id' => 'Standard ID'
        ];
    }

    public function getStandard()
    {
        return $this->hasOne(Standard::className(), ['id' => 'standard_id']);
    }
}
