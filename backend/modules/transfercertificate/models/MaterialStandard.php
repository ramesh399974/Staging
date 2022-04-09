<?php

namespace app\modules\transfercertificate\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\modules\master\models\Standard;

class MaterialStandard extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tc_material_standard';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['material_id', 'standard_id'], 'integer']
        ];
    }

    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'material_id' => 'Material ID',
            'standard_id' => 'Standard ID'
        ];
    }

    public function getStandard()
    {
        return $this->hasOne(Standard::className(), ['id' => 'standard_id']);
    }
}
