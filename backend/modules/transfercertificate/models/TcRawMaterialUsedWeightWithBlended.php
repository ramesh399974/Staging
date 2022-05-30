<?php

namespace app\modules\transfercertificate\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\modules\master\models\User;

class TcRawMaterialUsedWeightWithBlended extends \yii\db\ActiveRecord
{
	
    public static function tableName()
    {
        return 'tbl_tc_raw_material_used_weight_with_blended';
    }

    
   
    public function rules()
    {
        return [
            [['tc_raw_material_id'], 'integer']
        ];
    }

   
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tc_raw_material_used_weight_id' => 'TC Raw Material Used Weight ID'
        ];
    }	 
}
