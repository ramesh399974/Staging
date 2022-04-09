<?php

namespace app\modules\transfercertificate\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

use app\modules\application\models\Application;
use app\modules\application\models\ApplicationUnit;
use app\modules\application\models\ApplicationStandard;
use app\modules\application\models\ApplicationUnitProduct;

/**
 * This is the model class for table "tbl_tc_request_product_multiple".
 *
 * @property int $id
 
 */
class RequestProductMultiple extends \yii\db\ActiveRecord
{
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tc_request_product_multiple';
    }

    public function behaviors()
    {
        return [
           
            
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_by' => 'Updated By',
            'updated_at' => 'Updated At',
        ];
    }	
    
}
