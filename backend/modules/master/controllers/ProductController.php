<?php
namespace app\modules\master\controllers;

use Yii;
use app\modules\master\models\Product;
use app\modules\master\models\ProductStandard;
use app\modules\master\models\ProductType;
use app\modules\master\models\ProductTypeMaterialComposition;
use app\modules\application\models\ApplicationProduct;
use app\modules\application\models\ApplicationUnitProduct;
use app\modules\application\models\ApplicationProductMaterial;
use app\modules\application\models\ApplicationProductStandard;
use app\modules\application\models\ApplicationProductDetails;

use yii\web\NotFoundHttpException;

use sizeg\jwt\Jwt;
use sizeg\jwt\JwtHttpBearerAuth;

/**
 * ProductController implements the CRUD actions for Product model.
 */
class ProductController extends \yii\rest\Controller
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
			'authenticator' => ['class' => JwtHttpBearerAuth::class ]
		];        
    }
	
	public function actionIndex()
    {
		if(!Yii::$app->userrole->hasRights(array('product_category_master')))
		{
			return false;
		}

		$post = yii::$app->request->post();
		
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		
		$model = Product::find()->where(['<>','status',2]);

		if(is_array($post) && count($post)>0 && isset($post['standardFilter']) && is_array($post['standardFilter']) && count($post['standardFilter']))
		{
			$model = $model->join('inner join','tbl_product_standard as ps','ps.product_id=tbl_product.id')->where(['ps.standard_id'=>$post['standardFilter']]);
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
					['like', 'name', $searchTerm],
					['like', '(date_format(FROM_UNIXTIME(`created_at` ), \'%b %d, %Y\' ))', $searchTerm],
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
		
		$product_list=array();
		//$model = $model->asArray()->all();
		$model = $model->all();		
		if(count($model)>0)
		{
			foreach($model as $product)
			{
				$data=array();
				$data['id']=$product->id;
				$data['name']=$product->name;
				$data['code']=$product->code;
				$data['status']=$product->status;
				//$data['created_at']=date('M d,Y h:i A',$product->created_at);
				$data['created_at']=date($date_format,$product->created_at);

				$productstandards = $product->productstandard;
				if(count($productstandards)>0)
				{
					$standard_id_arr = array();
					$standard_ids_arr = array();
					foreach($productstandards as $val)
					{
						if($val->standard!==null)
						{
							$standard_id_arr[]="".$val['standard_id'];
							$standard_ids_arr[]=($val->standard ? $val->standard->code : '');
						}
					}
					$data["standard_id"]=$standard_id_arr;
					$data["standard_ids"]=implode(', ',$standard_ids_arr);
					
				}
				$product_list[]=$data;
			}
		}
		$materialtype = new ProductTypeMaterialComposition;
		$materialtypearr=[];
		foreach($materialtype->material_type as $key => $materialtype){
			$arr = [];
			$arr['id'] = $key;
			$arr['name'] = $materialtype;
			$materialtypearr[] = $arr;
		}

		return ['products'=>$product_list,'total'=>$totalCount,'material_type'=>$materialtypearr];
    }
	
	public function actionGetProduct()
	{
		$Product = Product::find()->select(['id','name'])->where(['status'=>0])->asArray()->all();
		
		$materialtype = new ProductTypeMaterialComposition;
		$materialtypearr=[];
		foreach($materialtype->material_type as $key => $materialtype){
			$arr = [];
			$arr['id'] = $key;
			$arr['name'] = $materialtype;
			$materialtypearr[] = $arr;
		}
		
		return ['products'=>$Product,'material_type'=>$materialtypearr];
	}

	public function actionGetStandardProduct()
	{
		$data = Yii::$app->request->post();

		$product_std_ids = $this->getProductStandardID();
		$Product =[];
		$materialtypearr=[];
		if($data){
			$Product = Product::find()->select(['pr.id','pr.name'])->alias('pr');
			$Product = $Product->join('inner join','tbl_product_standard as prs','prs.product_id=pr.id')->where(['pr.status'=>0,'prs.standard_id'=>$data['standard_id']])->groupBy('pr.id')->asArray()->all();

			// if(count($data['standard_id'])>1){
			// 	$Product = $Product->having('COUNT(prs.id)>1')->groupBy('pr.id')->asArray()->all();
			// }else{
			// 	$Product = $Product->andWhere(['not in','pr.id',$product_std_ids])->asArray()->all();
			// }
			
			$materialtype = new ProductTypeMaterialComposition;
			$materialtypearr=[];
			foreach($materialtype->material_type as $key => $materialtype){
				$arr = [];
				$arr['id'] = $key;
				$arr['name'] = $materialtype;
				$materialtypearr[] = $arr;
			}
		}
		return ['products'=>$Product,'material_type'=>$materialtypearr];
	}

    public function actionCreate()
	{
		if(!Yii::$app->userrole->hasRights(array('add_product_category')))
		{
			return false;
		}
		
		$model = new Product();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if (Yii::$app->request->post()) 
		{
			$data = Yii::$app->request->post();
			
			$model->name=$data['name'];
			$model->code=$data['code'];
			$model->description=$data['description'];
			
			$userData = Yii::$app->userdata->getData();
			$model->created_by=$userData['userid'];
			
			if($model->validate() && $model->save())
			{
				if(isset($data['standard_id']) && is_array($data['standard_id'])){
					foreach($data['standard_id'] as $sid){
						$pro_stan_model = new ProductStandard();
						$pro_stan_model->product_id = $model->id;
						$pro_stan_model->standard_id = $sid;
						$pro_stan_model->save();
					}
				}
				$responsedata=array('status'=>1,'message'=>'Product category has been created successfully');	
			}
			else
			{
				$responsedata=array('status'=>0,'message'=>$model->errors);
			}
		}
		return $this->asJson($responsedata);
	}

    public function actionUpdate()
    {
		if(!Yii::$app->userrole->hasRights(array('edit_product_category')))
		{
			return false;
		}

		$data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($data) 
		{
           	$model = Product::find()->where(['id' => $data['id']])->one();
			if ($model !== null)
			{
				$model->name=$data['name'];
				$model->code=$data['code'];
				$model->description=$data['description'];
				
				$userData = Yii::$app->userdata->getData();
				$model->updated_by=$userData['userid'];
			
				if($model->validate() && $model->save())
				{
					ProductStandard::deleteAll(['product_id'=>$data['id']]);

					if(isset($data['standard_id']) && is_array($data['standard_id'])){
						foreach($data['standard_id'] as $sid){
							$pro_stan_model = new ProductStandard();
							$pro_stan_model->product_id = $model->id;
							$pro_stan_model->standard_id = $sid;
							$pro_stan_model->save();
						}
					}
					$responsedata=array('status'=>1,'message'=>'Product category has been updated successfully');
				}
				else
				{
					$responsedata=array('status'=>0,'message'=>$model->errors);
				}
			}
			else
			{
				$responsedata=array('status'=>0,'message'=>$model->errors);
			}
            return $this->asJson($responsedata);
        }
    }

    public function actionView()
    {
		$data = Yii::$app->request->post();
        $model = $this->findModel($data['id']);

		if ($model !== null)
		{
			if(!Yii::$app->userrole->hasRights(array('product_category_master')))
			{
				return false;
			}

			$resultarr=array();
			$resultarr["id"]=$model->id;
			$resultarr["name"]=$model->name;
			$resultarr["code"]=$model->code;
			$resultarr["description"]=$model->description;
			
			$productstandards = $model->productstandard;
				if(count($productstandards)>0)
				{
					$standard_id_arr = array();
					$standard_ids_arr = array();
					foreach($productstandards as $val)
					{
						
							$standard_id_arr[]="".$val['standard_id'];
							$standard_ids_arr[]=($val->standard ? $val->standard->code : '');
						
					}
					$resultarr["standard_id"]=$standard_id_arr;
					$resultarr["standard_ids"]=implode(', ',$standard_ids_arr);
					
				}
            return ['data'=>$resultarr];
        }
    }

	public function actionProducttype($id)
    {
        $ProductType = ProductType::find()->where(['status'=>0,'product_id'=>$id])->all();                
        $currentTotal = count($ProductType);
        if($currentTotal > 0 )
        {
            return array('status' => true, 'data'=> $ProductType);
        }
        else
        {
            return array('status'=>false,'data'=> array());
        }
    }
	
	public function actionCommonUpdate()
	{
		$data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($data && isset($data['status'])) 
		{
			$id=$data['id'];
			$status = $data['status'];	

			if(!Yii::$app->userrole->canDoCommonUpdate($status,'product_category'))
			{
				return false;
			}	

           	$model = Product::find()->where(['id' => $id])->one();
			if ($model !== null)
			{
				$model->status=$data['status'];
				$userData = Yii::$app->userdata->getData();
				$model->updated_by=$userData['userid'];			
				if($model->validate() && $model->save())
				{
					$msg='';
					if($model->status==0){
						$msg='Product category has been activated successfully';
					}elseif($model->status==1){
						$msg='Product category has been deactivated successfully';
					}elseif($model->status==2){
						/*
						$exists=0;

                        if(ApplicationProduct::find()->where( [ 'product_id' => $id ] )->exists()){
                            $exists=1;
                        }elseif(ApplicationUnitProduct::find()->where( [ 'product_id' => $id ] )->exists()){
                            $exists=1;
                        }elseif(ApplicationProductMaterial::find()->where( [ 'app_product_id' => $id ] )->exists()){
                            $exists=1;
                        }elseif(ProductType::find()->where( [ 'product_id' => $id ] )->exists()){
                            $exists=1;
                        }elseif(ApplicationProductDetails::find()->where( [ 'product_id' => $id ] )->exists()){
                            $exists=1;
                        }elseif(ProductTypeMaterialComposition::find()->where( [ 'product_id' => $id ] )->exists()){
                            $exists=1;
                        }elseif(ApplicationProductStandard::find()->where( [ 'application_product_id' => $id ] )->exists()){
                            $exists=1;
                        }else{
                            $exists=0;
						}
						
						if($exists==0)
                        {
                            //Product::findOne($id)->delete();
						}
						*/
						$msg='Product has been deleted successfully';
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
	
	/**
     * Finds the Country model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Country the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Product::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

	protected function getProductStandardID()
	{
		$model = ProductStandard::find()->all();

		$ids =[];
		$duplicate_ids = [];
		if(count($model)>0)
		{
			foreach($model as $id){
				if(!in_array($id['product_id'],$ids)){
					$ids[]=$id['product_id'];
				}else {
					$duplicate_ids[] = $id['product_id'];
				}
			}
			$duplicate_ids = array_unique($duplicate_ids);
		}
		return $duplicate_ids;
	}
}
