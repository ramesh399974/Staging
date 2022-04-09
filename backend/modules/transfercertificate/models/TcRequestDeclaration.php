<?php

namespace app\modules\transfercertificate\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\modules\master\models\User;

class TcRequestDeclaration extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tc_request_declarations';
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',            
            'standard_id' => 'Name',
            'status' => 'Status',
        ];
    }	
			
}
