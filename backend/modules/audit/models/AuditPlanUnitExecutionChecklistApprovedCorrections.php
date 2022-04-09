<?php

namespace app\modules\audit\models;

use Yii;

class AuditPlanUnitExecutionChecklistApprovedCorrections extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'tbl_audit_plan_unit_execution_checklist_approved_corrections';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['audit_plan_unit_execution_checklist_id'], 'integer']
        ];
    }

}