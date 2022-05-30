<?php

namespace app\modules\master\models;

use Yii;

/**
 * This is the model class for table "tbl_user_declaration_questions".
 *
 * @property int $id
 * @property string $type
 * @property string $question
 */

class UserDeclarationQuestions extends \yii\db\ActiveRecord
{
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_user_declaration_questions';
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'question' => 'Question',
        
        ];
    }
    
}
