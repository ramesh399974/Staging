<?php

namespace app\modules\master\models;

use Yii;

/**
 * This is the model class for table "tbl_audit_execution_question_findings_history".
 *
 * @property int $id
 * @property int $audit_execution_question_id
 * @property int $question_finding_id
 */
class AuditExecutionQuestionFindingsHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_audit_execution_question_findings_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['audit_execution_question_history_id', 'question_finding_id'], 'integer'],
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
            'question_finding_id' => 'Question Finding ID',
        ];
    }
}
