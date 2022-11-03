<?php

namespace app\modules\audit\models;

use Yii;
use app\modules\master\models\User;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
/**
 * This is the model class for table "tbl_audit_plan_reviewer".
 *
 * @property int $id
 * @property int $audit_plan_id
 * @property int $reviewer_id
 * @property int $reviewer_status 1=Current,2=Old
 * @property int $created_by
 * @property int $created_at
 */
class AuditPlanExecutionQuestions extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'tbl_audit_plan_execution_questions';
    }

    public function behaviors()
    {
        return [];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['audit_plan_id', 'question_id','q_version'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'audit_plan_id' => 'Audit Plan ID',
            'question_id' => 'Question ID',
            'q_version' => 'Question Version',
        ];
    }


}
