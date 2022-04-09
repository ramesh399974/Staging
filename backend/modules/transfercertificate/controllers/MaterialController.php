<?php
namespace app\modules\transfercertificate\controllers;
use app\modules\transfercertificate\models\Material;
use app\modules\transfercertificate\models\MaterialStandard;

use Yii;

use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;



use sizeg\jwt\Jwt;
use sizeg\jwt\JwtHttpBearerAuth;
/**
 * ProductTypeMaterialCompositionController implements the CRUD actions for Product model.
 */
class MaterialController extends \yii\rest\Controller
{
    /**
     * {@inheritdoc}
     */
public function behaviors()
    {

        return [
			[
				'class' => \yii\filters\ContentNegotiator::className(),
				//'only' => ['index', 'view'],
				'formats' => [
					'application/json' => \yii\web\Response::FORMAT_JSON,
				],
            ],
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
			],
			'authenticator' => ['class' => JwtHttpBearerAuth::class]
		];        
    }

    public function actionIndex()
    {
		if(!Yii::$app->userrole->hasRights(array('material_tc')))
		{
			return false;
		}

        //$model = Standard::find()->select(['id','name'])->asArray()->all();
        //return ['data'=>$model];
		$post = yii::$app->request->post();
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		$model = Material::find()->where(['<>','t.status',2])->alias('t');
		
		
		$model = $model->groupBy(['t.id']);
		if(is_array($post) && count($post)>0 && isset($post['standardFilter']) && is_array($post['standardFilter']) && count($post['standardFilter'])>0)
		{
			$model = $model->join('inner join','tbl_tc_material_standard as ptms','ptms.material_id=t.id')->where(['ptms.standard_id'=>$post['standardFilter']]);
		}
		
		if(is_array($post) && count($post)>0 && isset($post['page']) && isset($post['pageSize']))
		{
            $page = ($post['page'] - 1)*$post['pageSize'];
            $pageSize = $post['pageSize']; 
			
			if(isset($post['searchTerm']))
			{
				$searchTerm = $post['searchTerm'];
				$model = $model->andFilterWhere([
					'or',
					['like', 't.name', $searchTerm],
					['like', 't.code', $searchTerm],
					['like', 'prd.name', $searchTerm],
					['like', 'ptype.name', $searchTerm],
					['like', '(date_format(FROM_UNIXTIME(t.created_at), \'%b %d, %Y\' ))', $searchTerm],
				]);

				$totalCount = $model->count();
			}
			$sortDirection = isset($post['sortDirection']) && $post['sortDirection']=='desc' ?SORT_DESC:SORT_ASC;
			if(isset($post['sortColumn']) && $post['sortColumn'] !='')
			{
				$model = $model->orderBy([$post['sortColumn']=>$sortDirection]);
			}
			else
			{
				$model = $model->orderBy(['created_at' => SORT_DESC]);
			}
            $model = $model->limit($pageSize)->offset($page);
		}
		else
		{
			$totalCount = $model->count();
		}
		
		$list=array();
		//$model->Where(['<>','t.status',2]);
		$model = $model->all();		
		if(count($model)>0)
		{
			foreach($model as $material)
			{
				$data=array();
				$data['id']=$material->id;
				$data['name']=$material->name;
				$data['code']=$material->code;
				$data['status']=$material->status;
				$data['created_at']=date($date_format,$material->created_at);

				$mat_standard = $material->materialstandard;
				$std_code = array();
				if(count($mat_standard)>0){
					foreach($mat_standard as $m_ids){
						$std_code[]=$m_ids->standard->code;
					}
					$data['std_code']=implode(', ',$std_code);
				}
				$list[]=$data;
			}
		}
		
		return ['materialcompositions'=>$list,'total'=>$totalCount];
	}
	


	/*
	public function actionSearchlist()
    {
		$post = yii::$app->request->post();
		$lists = [];

		$searchname = $post['searchname'];
		if($searchname){
			$list = ProductTypeMaterialComposition::find()->select(['name'])->where(['like','name',$searchname])->asArray()->all();
			$lists = ArrayHelper::getColumn($list, 'name');
		}
		return $lists;
	}
	*/
	
    public function actionCreate()
    {
		if(!Yii::$app->userrole->hasRights(array('add_tc_material')))
		{
			return false;
		}

		$data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($data) 
		{
			$model = new Material();
			$model->name=$data['name'];
			$model->code=$data['code'];
			//$model->code='';
			//$model->description=$value['description'];
			$userData = Yii::$app->userdata->getData();
			$model->created_by=$userData['userid'];
			if($model->validate() && $model->save())
			{
				if( isset($data['standard_id']) && is_array($data['standard_id'])){
					foreach($data['standard_id'] as $sid){
						$pro_stan_model = new MaterialStandard();
						$pro_stan_model->material_id = $model->id;
						$pro_stan_model->standard_id = $sid;
						$pro_stan_model->save();
					}
				}
				
				
				$responsedata=array('status'=>1,'message'=>'Material has been created successfully');
			}else
			{
				$responsedata=array('status'=>0,'message'=>$model->errors);
			}          
        }
		return $this->asJson($responsedata);
    }

    
    public function actionUpdate()
    {
		if(!Yii::$app->userrole->hasRights(array('edit_tc_material')))
		{
			return false;
		}

        $data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($data) 
		{
			$model = Material::find()->where(['id' => $data['id']])->one();
			if ($model !== null)
			{
				
				$model->name=$data['name'];
				$model->code=$data['code'];
				
				$userData = Yii::$app->userdata->getData();
				$model->created_by=$userData['userid'];
				if($model->validate() && $model->save())
				{
					MaterialStandard::deleteAll(['material_id'=>$data['id']]);

					if( isset($data['standard_id']) && is_array($data['standard_id'])){
						foreach($data['standard_id'] as $sid){
							$pro_stan_model = new MaterialStandard();
							$pro_stan_model->material_id = $model->id;
							$pro_stan_model->standard_id = $sid;
							$pro_stan_model->save();
						}
					}

					$responsedata=array('status'=>1,'message'=>'Material has been updated successfully'); 
				}else
				{
					$responsedata=array('status'=>0,'message'=>$model->errors);
				}
			}
			         
        }
		return $this->asJson($responsedata);
    }

    public function actionView()
    {
		$resultarr = array();
		if(!Yii::$app->userrole->hasRights(array('material_tc')))
		{
			return false;
		}

		$data = Yii::$app->request->post();
		
        $model = $this->findModel($data['id']);
        if ($model !== null)
		{ 
			$resultarr['data']=$model;
			$std_ids = [];
			$material_std = MaterialStandard::find()->where(['material_id'=>$data['id']])->all();
			if(count($material_std)>0){
				foreach($material_std as $mstd){
					$std_ids[]=$mstd->standard_id;
				}
			}
			$resultarr['std']=$std_ids;
            return $resultarr;
        }

	}
	
	public function actionCommonUpdate()
	{
		if(!Yii::$app->userrole->hasRights(array('delete_tc_material','activate_tc_material','deactivate_tc_material')))
		{
			return false;
		}

		$data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($data && isset($data['status'])) 
		{
			$id=$data['id'];
			$status = $data['status'];

			if(!Yii::$app->userrole->canDoCommonUpdate($status,'material'))
			{
				return false;
			}	

           	$model = Material::find()->where(['id' => $id])->one();
			if ($model !== null)
			{
				$model->status=$data['status'];
				$userData = Yii::$app->userdata->getData();
				$model->updated_by=$userData['userid'];			
				if($model->validate() && $model->save())
				{
					$msg='';
					if($model->status==0){
						$msg='Material has been activated successfully';
					}elseif($model->status==1){
						$msg='Material has been deactivated successfully';
					}elseif($model->status==2){
						/*
						$exists=0;

                        if(ApplicationProductMaterial::find()->where( [ 'material_id' => $id ] )->exists()){
                            $exists=1;
						}
						
						if($exists==0)
                        {
                            //ProductTypeMaterialComposition::findOne($id)->delete();
						}
						*/
						$msg='Material has been deleted successfully';
					}
					$responsedata=array('status'=>1,'message'=>$msg);
				}
				else
				{
					$arrerrors=array();
					$errors=$model->errors;
					if(is_array($errors) && count($errors)>0)
					{
						foreach($errors as $err)
						{
							$arrerrors[]=implode(",",$err);
						}
					}
					$responsedata=array('status'=>0,'message'=>implode(",",$arrerrors));
				}
			}
			else
			{
				$responsedata=array('status'=>0,'message'=>$model->errors);
			}
            return $this->asJson($responsedata);
        }
	}

	public function actionSearchlistmaterialname()
    {
		$post = yii::$app->request->post();
		$material_list = array();

		$std_ids = $post['standard_ids'];
		
		
		
		// $stds='';
		// foreach($post["standard_ids"] as $value)
		// {
		// 	$stds.=$value.",";
		// }
		// $std_ids=substr($stds, 0, -1);
		
		
		
		
		$connection = Yii::$app->getDb();
		$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();
		if(is_array($std_ids) && count($std_ids)>0){
			$command = $connection->createCommand("SELECT ptm.name,ptm.id
			from tbl_tc_material as ptm
			INNER JOIN tbl_tc_material_standard as ptms on ptms.material_id = ptm.id
			WHERE ptms.standard_id IN (".implode(',',$std_ids).")
			group by name");
		
			$result = $command->queryAll();
	
			if(count($result)>0){
				foreach($result as $val){
					$data['id']=$val['id'];
					 $data['name']=$val['name'];
					 $material_list[]=$data;
				}
			}
		}
		
	 return $material_list;
	}
	
	/**
     * Finds the ProductType model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return ProductType the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Material::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

	protected function getMaterialStandardID()
	{
		$mat_model = MaterialStandard::find()->all();

		$ids = array();
		$duplicate_ids = array();
		if(count($mat_model)>0){
			foreach($mat_model as $id)
			{
				if(!in_array($id['material_id'],$ids)){
					$ids[] = $id['material_id'];
				}else{
					$duplicate_ids[] = $id['material_id'];
				}
			}
			$duplicate_ids = array_unique($duplicate_ids);
		}
		return $duplicate_ids;
	}
}