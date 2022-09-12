<?php

namespace app\modules\audit\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\modules\master\models\User;

class AuditReportInterviewEmployeesTiming extends \yii\db\ActiveRecord
{
    
    public static function tableName()
    {
        return 'tbl_audit_report_interview_employees_timing';
    }

   
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [];
    } 
}
