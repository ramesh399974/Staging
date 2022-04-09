<?php

namespace app\modules\transfercertificate\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\modules\master\models\User;

class RawMaterialName extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tc_raw_material_name';
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',            
            'standard_id' => 'Standard Id',
            'name' => 'Name',
            'status' => 'Status',
        ];
    }	
			
}
