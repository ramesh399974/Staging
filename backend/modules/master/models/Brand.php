<?php

namespace app\modules\master\models;

use Yii;


class Brand extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $arrStatus=array('1'=>'Active','2'=>'In-Active','3'=>'Deleted');
    public $arrEnumStatus=array('active'=>'1','inactive'=>'2','deleted'=>'3');

    public static function tableName()
    {
        return 'tbl_brands';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'string'],
            [['number'], 'string'],
            [['version'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id'=>'User Id',
            'brand_group_id'=>'Brand Group Id',
            'name' => 'Brand Name',
            'number' => 'Number',
            'version' => 'Version',
        ];
    }

    public function getBrandgroup()
    {
        return $this->hasOne(BrandGroup::className(),['id'=>'brand_group_id']);
    }
}
