<?php

namespace app\modules\transfercertificate\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class RawMaterialAttachments extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'tbl_tc_raw_material_attachments';
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'raw_material_id' => 'Raw Material ID',
            'type' => 'Attachment Type',
            'attachments' => 'Attachments'
        ];
    }

}