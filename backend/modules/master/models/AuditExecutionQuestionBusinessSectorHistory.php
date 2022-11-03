<?php

namespace app\modules\master\models;

use Yii;

/**
 * This is the model class for table "tbl_audit_execution_question_business_sector_history".
 *
 * @property int $id
 * @property int $audit_execution_question_id
 * @property int $business_sector_id
 */
class AuditExecutionQuestionBusinessSectorHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_audit_execution_question_business_sector_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['audit_execution_question_history_id', 'business_sector_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'audit_execution_question_history_id' => 'Audit Execution Question History ID',
            'business_sector_id' => 'Business Sector ID',
        ];
    }

 
}
