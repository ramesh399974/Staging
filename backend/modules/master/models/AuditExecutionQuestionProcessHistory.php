<?php

namespace app\modules\master\models;

use Yii;

/**
 * This is the model class for table "tbl_audit_execution_question_process_history".
 *
 * @property int $id
 * @property int $audit_execution_question_id
 * @property int $process_id
 */
class AuditExecutionQuestionProcessHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_audit_execution_question_process_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['audit_execution_question_history_id', 'process_id'], 'integer'],
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
            'process_id' => 'Process ID',
        ];
    }

 
}
