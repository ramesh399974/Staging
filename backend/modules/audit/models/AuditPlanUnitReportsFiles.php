<?php

namespace app\modules\audit\models;
use app\modules\application\models\ApplicationUnit;
use app\modules\master\models\User;
use Yii;


class AuditPlanUnitReportsFiles extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'tbl_audit_plan_unit_reports_files';
    }

    /**
     * {@inheritdoc}
     */
    

    public function rules()
    {
        return [
            
        ];
    }

   
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'audit_plan_unit_id' => 'Audit Plan Unit ID',
            'audit_plan_unit_reports_id' => 'Audit Plan Unit Reports ID',
            'filename' => 'Report Filename',
        ];
    }

   
}
