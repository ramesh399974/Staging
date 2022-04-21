<?php

namespace app\modules\transfercertificate\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\modules\master\models\Country;
use app\modules\master\models\State;
/**
 * This is the model class for table "tbl_tc_raw_material_location_country".
 *
 * @property int $id
 * @property int $raw_material_id
 * @property string $country_id
 * @property string $state_id
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_by
 * @property int $updated_at
 */
class RawMaterialLocationCountry extends \yii\db\ActiveRecord
{
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tc_raw_material_location_country';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['raw_material_id','created_by','created_at', 'updated_by', 'updated_at'], 'integer'],
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
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'updated_at' => 'Updated At',
        ];
    }

    public function getGeolocationcountrystate()
    {
        return $this->hasMany(RawMaterialLocationCountryState::className(), ['raw_material_location_country_id' => 'id']);
    }
    public function getCountry()
    {
        return $this->hasOne(Country::className(), ['id' => 'country_id']);
    }
	
}
