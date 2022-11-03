<?php

namespace app\modules\master\models;

use Yii;

/**
 * This is the model class for table "tbl_audit_execution_question_standard_history".
 *
 * @property int $id
 * @property int $audit_execution_question_id
 * @property int $standard_id
 * @property string $clause_no
 * @property string $clause
 */
class AuditExecutionQuestionStandardHistory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_audit_execution_question_standard_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['audit_execution_question_history_id', 'standard_id'], 'integer'],
            [['clause'], 'string'],
            [['clause_no'], 'string', 'max' => 255],
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
            'standard_id' => 'Standard ID',
            'clause_no' => 'Clause No',
            'clause' => 'Clause',
        ];
    }

}
