<?php
namespace app\modules\master\controllers;

use Yii;
use app\modules\master\models\AuditFileUploadsStandard;
use app\modules\master\models\AuditFileUploadsProcess;
use app\modules\master\models\AuditFileUploads;

use yii\web\NotFoundHttpException;

use sizeg\jwt\Jwt;
use sizeg\jwt\JwtHttpBearerAuth;


/**
 * ProcessController implements the CRUD actions for Process model.
 */
class FileUploadsController extends \yii\rest\Controller
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
		if(!Yii::$app->userrole->hasRights(array('files_uploads_master')))
		{
			return false;
		}

		$post = yii::$app->request->post();
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		$model = AuditFileUploads::find()->where(['<>','status',2]);
		if(is_array($post) && count($post)>0 && isset($post['standardFilter']) && is_array($post['standardFilter']) && count($post['standardFilter']))
		{
			$model = $model->join('inner join','tbl_audit_file_uploads_standard as fs','fs.audit_file_upload_id=tbl_audit_file_uploads.id')->where(['fs.standard_id'=>$post['standardFilter']]);
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
					['like', 'report_name', $searchTerm],
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
		
		$process_list=array();
		//$model = $model->asArray()->all();
		$model = $model->all();		
		if(count($model)>0)
		{
			foreach($model as $file)
			{
				$stdmod = $file->auditfileuploadsstandard;
				$stdarr = array();
				if(count($stdmod)>0){
					foreach($stdmod as $std)
					{
						$stdarr[]=$std->standard->code;
					}
				}

				$prsmod = $file->auditfileuploadsprocess;
				$prsarr = array();
				if(count($prsmod)>0){
					foreach($prsmod as $prs)
					{
						$prsarr[]=$prs->process->name;
					}
				}

				$data=array();
				$data['id']=$file->id;
				$data['name']=$file->report_name;
				$data['status']=$file->status;
				$data['standard_ids']=implode(',',$stdarr);
				$data['process_ids']=implode(',',$prsarr);
				$data['status']=$file->status;
				$data['created_at']=date($date_format,$file->created_at);
				$process_list[]=$data;
			}
		}
		
		return ['files'=>$process_list,'total'=>$totalCount];
    }
	
	public function actionGetProcess()
	{
		$Country = Process::find()->select(['id','name'])->where(['status'=>0])->asArray()->all();
		return ['processes'=>$Country];
	}

    public function actionCreate()
	{
		if(!Yii::$app->userrole->hasRights(array('add_file_uploads_master')))
		{
			return false;
		}

		$model = new AuditFileUploads();		
		$data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($data) 
		{		
			$model->report_name=$data['name'];
			
			$userData = Yii::$app->userdata->getData();
			$model->created_by=$userData['userid'];
			
			if($model->validate() && $model->save())
			{
                if(isset($data['standard_id']) && is_array($data['standard_id'])){
					foreach($data['standard_id'] as $sid){
						$stan_model = new AuditFileUploadsStandard();
						$stan_model->audit_file_upload_id = $model->id;
						$stan_model->standard_id = $sid;
						$stan_model->save();
					}
				}

                if(isset($data['process_id']) && is_array($data['process_id'])){
					foreach($data['process_id'] as $pid){
						$pro_model = new AuditFileUploadsProcess();
						$pro_model->audit_file_upload_id = $model->id;
						$pro_model->process_id = $pid;
						$pro_model->save();
					}
				}

				$responsedata=array('status'=>1,'message'=>'File Uploads has been created successfully');	
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
		if(!Yii::$app->userrole->hasRights(array('edit_file_uploads_master')))
		{
			return false;
		}

		$data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($data) 
		{
           	$model = AuditFileUploads::find()->where(['id' => $data['id']])->one();
			if ($model !== null)
			{
				$model->report_name=$data['name'];

				
				$userData = Yii::$app->userdata->getData();
				$model->updated_by=$userData['userid'];
			
				if($model->validate() && $model->save())
				{
					AuditFileUploadsStandard::deleteAll(['audit_file_upload_id'=>$data['id']]);

					if(isset($data['standard_id']) && is_array($data['standard_id'])){
						foreach($data['standard_id'] as $sid){
							$stan_model = new AuditFileUploadsStandard();
							$stan_model->audit_file_upload_id = $model->id;
							$stan_model->standard_id = $sid;
							$stan_model->save();
						}
					}

					AuditFileUploadsProcess::deleteAll(['audit_file_upload_id'=>$data['id']]);

					if(isset($data['process_id']) && is_array($data['process_id'])){
						foreach($data['process_id'] as $pid){
							$prs_model = new AuditFileUploadsProcess();
							$prs_model->audit_file_upload_id = $model->id;
							$prs_model->process_id = $pid;
							$prs_model->save();
						}
					}
					$responsedata=array('status'=>1,'message'=>'File Uploads has been updated successfully');
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
		if(!Yii::$app->userrole->hasRights(array('file_uploads_master')))
		{
			return false;
		}

		$data = Yii::$app->request->post();
		
        $model = $this->findModel($data['id']);
        if ($model !== null)
		{
			$stdmod = $model->auditfileuploadsstandard;
			$stdarr = array();
			if(count($stdmod)>0){
				foreach($stdmod as $std)
				{
					$stdarr[]=$std->standard_id;
				}
			}

			$prsmod = $model->auditfileuploadsprocess;
			$prsarr = array();
			if(count($prsmod)>0){
				foreach($prsmod as $prs)
				{
					$prsarr[]=$prs->process_id;
				}
			}
			$data=array();
			$data['id']=$model->id;
			$data['name']=$model->report_name;
			$data['status']=$model->status;
			$data['standard_id']=$stdarr;
			$data['process_id']=$prsarr;
			$data['status']=$model->status;
			$data['created_at']=date($date_format,$model->created_at);

            return ['data'=>$data];
        }

    }
	
	public function actionCommonUpdate()
	{   
	   	$data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($data && isset($data['status'])) 
		{
			$status = $data['status'];			
			if(!Yii::$app->userrole->canDoCommonUpdate($status,'file_uploads_master'))
			{
				return false;
			}		
		
			$id=$data['id'];
           	$model = AuditFileUploads::find()->where(['id' => $id])->one();
			if ($model !== null)
			{
				$model->status=$data['status'];
				$userData = Yii::$app->userdata->getData();
				$model->updated_by=$userData['userid'];			
				if($model->validate() && $model->save())
				{
					$msg='';
					if($model->status==0){						
						$msg='File Uploads has been activated successfully';
					}elseif($model->status==1){						
						$msg='File Uploads has been deactivated successfully';
					}elseif($model->status==2){						
						
						$msg='File Uploads has been deleted successfully';
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
        if (($model = AuditFileUploads::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
   
}
