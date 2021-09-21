<?php

namespace app\modules\transfercertificate\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\modules\master\models\User;
/**
 * This is the model class for table "tbl_tc_inspection_body".
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $status
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_by
 * @property int $updated_at
 */
class InspectionBody extends \yii\db\ActiveRecord
{
	public $arrStatus=array('0'=>'Approved','1'=>'Archived');
	public $enumStatus=array('approved'=>'0','archived'=>'1');	
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tc_inspection_body';
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
    public function rules()
    {
        return [
            [['name', 'description'], 'string'],
            [['status', 'created_by', 'created_at', 'updated_by', 'updated_at'], 'integer'],
			['name', 'unique', 'targetAttribute' => ['name', 'type'],'filter' => ['!=','status' ,1]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',							
            'status' => 'Status',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_by' => 'Updated By',
            'updated_at' => 'Updated At',
        ];
    }	
    
    public function getCreatedbydata()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }
}
