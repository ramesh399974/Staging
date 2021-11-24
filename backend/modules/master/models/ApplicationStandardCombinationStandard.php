<?php

namespace app\modules\master\models;

use Yii;

use app\modules\master\models\User;
use app\modules\master\models\Role;
/**
 * This is the model class for table "tbl_application_standard_combination_standard".
 *
 * @property int $id
 * @property int $appli_standard_combination_id
 * @property int $appli_standard_id
 */
class ApplicationStandardCombinationStandard extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_application_standard_combination_standard';
    }

   
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['appli_standard_combination_id', 'appli_standard_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'appli_standard_combination_id' => 'Appli Standard Combination ID',
            'appli_standard_id' => 'Appli Standard ID',
        ];
    }
    
    public function getStandard()
    {
        return $this->hasOne(Standard::className(), ['id' => 'appli_standard_id']);
    }

}
