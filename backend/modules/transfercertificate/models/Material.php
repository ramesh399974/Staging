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
class Material extends \yii\db\ActiveRecord
{
	public $material_type= ['1'=>'Certified','2'=>'Non Certified'];
    public $arrEnumMaterialType= ['certified'=>'1','non-certified'=>'2'];
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tc_material';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'created_by', 'created_at', 'updated_by', 'updated_at'], 'integer'],
            [['name', 'code'], 'string', 'max' => 255],
			//[['product_id','product_type_id','name'], 'unique'],
			//['name', 'unique', 'targetAttribute' => ['product_id', 'name' => 'product_type_id']]
			['name', 'unique', 'targetAttribute' => ['name'],'filter' => ['!=','status' ,2],'message' => 'The "Material Name" has already been taken.'],
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
            'name' => 'Material Composition Name',
            'code' => 'Code',
            'status' => 'Status',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_by' => 'Updated By',
            'updated_at' => 'Updated At',
        ];
    }
	
    public function getMaterialstandard()
    {
        return $this->hasMany(MaterialStandard::className(), ['material_id' => 'id']);
    }
}
