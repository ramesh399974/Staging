<?php

namespace app\modules\library\models;

use Yii;
use yii\db\ActiveRecord;
use app\modules\master\models\User;
use app\modules\master\models\UserCompanyInfo;
/**
 * This is the model class for table "tbl_library_mail_standard".
 *
 * @property int $id
 * @property int $library_mail_id
 * @property int $oss_id
 */
class LibraryMailOSS extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_library_mail_oss';
    }

   
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['library_mail_id', 'oss_id'], 'integer'],
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
