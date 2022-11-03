<?php

namespace app\modules\master\models;

use Yii;

/**
 * This is the model class for table "tbl_audit_execution_question_non_conformity_history".
 *
 * @property int $id
 * @property int $audit_execution_question_id
 * @property int $audit_non_conformity_timeline_id
 */
class AuditExecutionQuestionNonConformityHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_audit_execution_question_non_conformity_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['audit_execution_question_history_id', 'audit_non_conformity_timeline_id'], 'integer'],
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
            'audit_non_conformity_timeline_id' => 'Audit Non Conformity Timeline ID',
        ];
    }
	
}
