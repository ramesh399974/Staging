<?php

namespace app\modules\transfercertificate\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "tbl_product_type_material".
 *
 * @property int $id
 * @property int $product_type_id
 * @property string $name
 * @property string $code
 * @property string $description
 * @property int $status
 * @property int $approval_status
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_by
 * @property int $updated_at
 */
class RawMaterialCertifiedWeight extends \yii\db\ActiveRecord
{
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tc_raw_material_certified_weight';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tc_request_id', 'tc_request_product_id', 'tc_raw_material_id', 'updated_at'], 'integer'],
        ];
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at','updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tc_request_id' => 'TC Request ID',
            'tc_request_product_id' => 'TC Request Product ID',
            'tc_raw_material_id' => 'Raw Material ID',
            'tc_raw_material_product_id' =>'Raw Material Product ID',
            'certified_weight' => 'Certified Weight',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'updated_at' => 'Updated At',
        ];
    }
	
}
