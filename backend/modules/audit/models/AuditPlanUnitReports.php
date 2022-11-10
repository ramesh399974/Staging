<?php

namespace app\modules\audit\models;
use app\modules\application\models\ApplicationUnit;
use app\modules\master\models\User;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

class AuditPlanUnitReports extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'tbl_audit_plan_unit_reports';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at','updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }

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
            'unit_id' => 'Unit ID',
            'report_id' => 'Report ID',
            'report_name' => 'Report Name',
        ];
    }

    public function getAuditreportsfiles()
    {
        return $this->hasMany(AuditPlanUnitReportsFiles::className(), ['audit_plan_unit_reports_id' => 'id']);
    }
}
