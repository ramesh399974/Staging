<?php

namespace app\modules\certificate\models;

use app\modules\master\models\Standard;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\modules\master\models\User;

class CertificateStandards extends \yii\db\ActiveRecord
{
   
    public static function tableName()
    {
        return 'tbl_certificate_standards';
    }

   
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['certificate_id', 'standard_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'certificate_id' => 'Certificate ID',
            'standard_id' => 'Standard ID'
        ];
    }

    public function getStandard()
    {
        return $this->hasOne(Standard::className(), ['id' => 'standard_id']);
    }

}
