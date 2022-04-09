<?php

namespace app\modules\transfercertificate\models;

use Yii;
/**
 * This is the model class for table "tbl_tc_raw_material_label_grade".
 *
 * @property int $id
 * @property string $raw_material_id
 * @property int $label_grade_id
 */
class RawMaterialProductMaterial extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tc_raw_material_product_material';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['raw_material_id', 'material_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'raw_material_id' => 'Name',          				
            'material_id' => 'Material Id',
        ];
    }	
    
    public function getMaterial()
    {
        return $this->hasOne(Material::className(), ['id' => 'material_id']);
    }
}
