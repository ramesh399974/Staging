<?php
namespace app\modules\transfercertificate\controllers;

use Yii;
use app\modules\certificate\models\Certificate;
use app\modules\transfercertificate\models\Request;
use app\modules\transfercertificate\models\TcRequestBrandConsent;
use app\modules\transfercertificate\models\RequestProduct;
use app\modules\transfercertificate\models\RequestProductMultiple;
use app\modules\transfercertificate\models\RequestEvidence;
use app\modules\transfercertificate\models\RequestStandard;
use app\modules\transfercertificate\models\RequestReviewer;
use app\modules\transfercertificate\models\RequestFranchiseComment;
use app\modules\transfercertificate\models\RequestReviewerComment;
use app\modules\transfercertificate\models\TcRawMaterialUsedWeight;
use app\modules\transfercertificate\models\TcRawMaterialUsedWeightWithBlended;
use app\modules\transfercertificate\models\TcRequestIfoamStandard;
use app\modules\transfercertificate\models\RawMaterial;

use app\modules\transfercertificate\models\TcRequestDeclaration;
use app\modules\application\models\Application;
use app\modules\application\models\ApplicationUnit;
use app\modules\application\models\ApplicationStandard;
use app\modules\application\models\ApplicationUnitStandard;
use app\modules\application\models\ApplicationProduct;
use app\modules\application\models\ApplicationUnitProduct;
use app\modules\application\models\ApplicationChangeAddress;
use app\modules\application\models\ApplicationProductStandard;
use app\modules\transfercertificate\models\TcIfoamStandard;
use app\modules\transfercertificate\models\Material;



use app\modules\transfercertificate\models\Buyer;


use app\modules\transfercertificate\models\InspectionBody;
 
use app\modules\transfercertificate\models\TcRequestProductInputMaterial;
use app\modules\transfercertificate\models\RawMaterialProduct;
use app\modules\transfercertificate\models\RawMaterialProductMaterial;

use app\modules\master\models\User;
use app\modules\master\models\StandardCombination;
use app\modules\master\models\Mandaycost;
use app\modules\master\models\Brand;
use app\modules\master\models\Country;
use app\modules\master\models\State;

use app\modules\invoice\models\InvoiceTax;
use app\modules\invoice\models\Invoice;
use app\modules\invoice\models\InvoiceDetails;
use app\modules\invoice\models\InvoiceDetailsStandard;
use app\modules\invoice\models\InvoiceStandard;
use app\modules\invoice\models\InvoiceTc;
use app\modules\transfercertificate\models\RawMaterialCertifiedWeight;
use app\modules\transfercertificate\models\RawMaterialLocationCountry;
use app\modules\transfercertificate\models\RawMaterialLocationCountryState;

use yii\web\NotFoundHttpException;

use sizeg\jwt\Jwt;
use sizeg\jwt\JwtHttpBearerAuth;

/**
 * RequestController implements the CRUD actions for Product modell.
 */
class RequestController extends \yii\rest\Controller
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
	
	public function appRelation($model)
	{
		$model = $model->innerJoinWith(['application as app']);				
	}
	
	public function appAddressRelation($model)
	{
		$model = $model->innerJoinWith(['applicationaddress as appaddress']);				
	}
	
	public function actionIndex()
    {
		$post = yii::$app->request->post();		
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		$modelObj = new Request();		
		if($post)
		{
			$userrole = Yii::$app->userrole;
			$userid=$userrole->user_id;				
			$user_type=$userrole->user_type;
			$role=$userrole->role;
			$rules=$userrole->rules;
			$franchiseid=$userrole->franchiseid;		
			$resource_access=$userrole->resource_access;
			$is_headquarters =$userrole->is_headquarters;
			$role_chkid=$userrole->role_chkid;	
		
			$applicationmod = new Application();
			$brand_mod = new Brand();
			$model = Request::find()->alias('t')->andWhere(['not in','t.status',[50]]);	
			if(isset($post['type'])  && $post['type'] =='1')
			{
				if(!Yii::$app->userrole->hasRights(['generate_tc_bill']) && !Yii::$app->userrole->hasRights(['view_tc_bill'])){
					return false;
				}
				$model = $model->andWhere(['t.status'=> $modelObj->arrEnumStatus['approved']])->andWhere(['t.invoice_status'=>'0']);
			}	

			if(isset($post['type']) && $post['type'] =='2')
			{
				if(!Yii::$app->userrole->hasRights(['view_tc_generated_bill'])){
					return false;
				}
				//$model = $model->andWhere(['t.status'=> $modelObj->arrEnumStatus['approved']])->andWhere(['t.invoice_status'=>[1,2,3]]);		
				$model = $model->andWhere(['t.status'=> $modelObj->arrEnumStatus['approved']])->andWhere(['!=','t.invoice_status','0']);		
			}
			
			if(isset($post['paymentStatusFilter'])  && $post['paymentStatusFilter']!='')
			{
				$model = $model->andWhere(['t.invoice_status'=> $post['paymentStatusFilter']]);				
			}
			
			if(isset($post['statusFilter'])  && $post['statusFilter']!='')
			{
				$model = $model->andWhere(['t.status'=> $post['statusFilter']]);				
			}

			if(isset($post['invoiceFilter'])  && $post['invoiceFilter']!='')
			{
				if($post['invoiceFilter']==1){
					$model = $model->andWhere(['t.invoice_type'=> $post['invoiceFilter'],'t.fasttrack_addtional_charges'=>$post['invoiceFilter']]);
				}else if($post['invoiceFilter']==2)
					$model = $model->andWhere(['or',['t.invoice_type'=> $post['invoiceFilter']],['t.fasttrack_addtional_charges'=>$post['invoiceFilter']]]);				
			}
			if(isset($post['appFilter'])  && $post['appFilter']!='' && count($post['appFilter'])>0)
			{
				$model = $model->andWhere(['t.app_id'=> $post['appFilter']]);				
			}
			
			if(isset($post['standardFilter']) && is_array($post['standardFilter']) && count($post['standardFilter'])>0)
			{
				$model = $model->join('inner join', 'tbl_tc_request_standard as request_standard','request_standard.tc_request_id =t.id');
				$model = $model->andWhere(['request_standard.standard_id'=> $post['standardFilter']]);			
			}
			if(isset($post['from_date']))
			{
				$model = $model->join('inner join','tbl_tc_request_reviewer_comment as rrc','rrc.tc_request_id=t.id');
				$model = $model->andWhere(['>=','rrc.created_at', strtotime($post['from_date'])]);			
			}

			if(isset($post['to_date']))
			{
				$model = $model->join('inner join','tbl_tc_request_reviewer_comment as trrc','trrc.tc_request_id=t.id');
				$model = $model->andWhere(['<=','trrc.created_at', strtotime($post['to_date'].' 23:59:59')]);			
			} 
			if(isset($post['companyNameFilter'])  && $post['companyNameFilter']!='')
			{
			$this->appRelation($model);
		    $model = $model->andWhere(['app.customer_id'=> $post['companyNameFilter']]);		
			}

			$model = $model->groupBy(['t.id']);

			$appJoinWithStatus=false;
			if($resource_access != '1')
			{
				if(!in_array('brand_management',$rules)){
					$appJoinWithStatus=true;
					$this->appRelation($model);
				}
			
				
				if($user_type== Yii::$app->params['user_type']['user'] && $is_headquarters!=1 ){					
					$model = $model->andWhere(' app.franchise_id="'.$franchiseid.'" ');
				}
				
				if($user_type== Yii::$app->params['user_type']['customer']){
					$model = $model->andWhere('app.customer_id="'.$userid.'"');
				}else if($user_type== Yii::$app->params['user_type']['user']   
						&& (in_array('add_tc_application',$rules) 
					 || in_array('edit_tc_application',$rules)
					 || in_array('delete_tc_application',$rules)  || in_array('view_tc_application',$rules)
					 || in_array('clone_tc_application',$rules)
					 ))
				{

				}else if($user_type== Yii::$app->params['user_type']['user'] && in_array('assign_as_oss_review_for_tc',$rules)){
					$model = $model->andWhere(' t.status>1 and app.franchise_id="'.$franchiseid.'" ');
				}else if($user_type== Yii::$app->params['user_type']['user'] && in_array('application_review',$rules)){
					/*
					
					&& (!in_array('add_tc_application',$rules) 
						 || !in_array('edit_tc_application',$rules)
						 || !in_array('delete_tc_application',$rules)  || !in_array('view_tc_application',$rules)
						 || !in_array('clone_tc_application',$rules)
						 || !in_array('assign_as_oss_review_for_tc',$rules)
						 */
					$model = $model->joinWith(['reviewer as reviewer']);	
					$model = $model->andWhere('(t.status= "'.$modelObj->arrEnumStatus['waiting_for_review'].'" or reviewer.user_id="'.$userid.'")');
				}else if($user_type== Yii::$app->params['user_type']['franchise']  && $is_headquarters!=1){
					if($resource_access == '5'){
						$userid = $franchiseid;
					}
					$model = $model->andWhere('t.status>1 and app.franchise_id="'.$userid.'"');
				}
				if($user_type==1 && in_array('view_brand',$rules) && $is_headquarters==1){
					$model = $model->join('inner join','tbl_application as app','app.id=t.app_id');
					$model = $model->join('inner join','tbl_application_brands as appbrand','app.id=appbrand.app_id');
					$model = $model->join('inner join','tbl_brands as bran','bran.id=appbrand.brand_id');
					$model = $model->andWhere(['bran.user_id'=>$userid])->andWhere(['appbrand.status'=>$applicationmod->arrBrandEnumStatus['approved'],'t.is_brand_consent'=>1,'bran.status'=>$brand_mod->arrEnumStatus['active']]);
				}else if($user_type==1 && in_array('brand_report',$rules) && $is_headquarters==1){
					$model = $model->join('inner join','tbl_application as app','app.id=t.app_id');
					$model = $model->join('inner join','tbl_application_brands as appbrand','app.id=appbrand.app_id');
					$model = $model->join('inner join','tbl_brands as bran','bran.id=appbrand.brand_id');
					$model = $model->join('inner join','tbl_brand_group as bg','bran.brand_group_id=bg.id');
					$model = $model->andWhere(['bg.user_id'=>$userid])->andWhere(['appbrand.status'=>$applicationmod->arrBrandEnumStatus['approved'],'t.is_brand_consent'=>1,'bran.status'=>$brand_mod->arrEnumStatus['active']]);
				}
					
			}
			
			if(isset($post['franchiseFilter']) && is_array($post['franchiseFilter']) && count($post['franchiseFilter'])>0)
			{
				if(!$appJoinWithStatus)
				{
					$appJoinWithStatus=true;
					$this->appRelation($model);
				}
				$model = $model->andWhere(['app.franchise_id'=> $post['franchiseFilter']]);	
			}
			if(isset($post['brandFilter']) && is_array($post['brandFilter']) && count($post['brandFilter'])>0)
			{
				$model = $model->join('inner join','tbl_tc_request_brand_consent as tbs','tbs.tc_request_id=t.id');
				$model = $model->andWhere(['tbs.brand_id'=> $post['brandFilter']]);	
			}	

			if(is_array($post) && count($post)>0 && isset($post['page']) && isset($post['pageSize']))
			{
				$page = ($post['page'] - 1)*$post['pageSize'];
				$pageSize = $post['pageSize']; 
				
				if(isset($post['searchTerm']) && $post['searchTerm'] !='')
				{
					$searchTerm = $post['searchTerm'];					
					
					// ---- Invoice Number Search Code Start Here ----
					$arrTCids=array();
					$connection = Yii::$app->getDb();	
					$connection->createCommand("SET SESSION group_concat_max_len = 1000000;")->execute();
					$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();							
					$command = $connection->createCommand('SELECT GROUP_CONCAT(t.tc_request_id) AS requestids FROM `tbl_tc_request_product` AS t where t.invoice_no like "%'.$searchTerm.'%"');
					$result = $command->queryOne();
					if($result !== false)
					{	
						$requestTCids=$result['requestids'];						
						$arrTCids=explode(",",$requestTCids);							
						//$model = $model->andWhere(['t.id'=> $arrTCids]);	
					}
					// ---- Invoice Number Search Code End Here ----
					
					$this->appAddressRelation($model);
					$model = $model->join('left join', 'tbl_tc_buyer as buyer','buyer.id=t.buyer_id and buyer.type=\'buyer\'');							
					//$model = $model->join('left join', 'tbl_tc_request_product as reqprd','reqprd.tc_request_id=t.id');					
					
					$model = $model->andFilterWhere([
						'or',						
						['like', 'appaddress.company_name', $searchTerm],
						['like', 'purchase_order_number', $searchTerm],
						//['like', 'reqprd.invoice_no', $searchTerm],
						['like', 'buyer.name', $searchTerm],
						['like', 't.tc_number', $searchTerm],	
						['like', 'CONCAT(t.id, \'TEMP\')', $searchTerm],						
						['t.id'=>$arrTCids]
					]);								
				}
				$totalCount = $model->count();
				
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
			$model = $model->all();		
			if(count($model)>0)
			{
				$usermodel = new User();
				foreach($model as $modelData)
				{	
					$data=array();
					$data['id']=$modelData->id;
					if($modelData->tc_type == 1){
					$data['app_id_label']=$modelData->applicationaddress?$modelData->applicationaddress->company_name:"";
				}
				else if($modelData->tc_type == 2){
					$facilityModelObject = $modelData->facilityaddress;
					$data["app_id_label"]=$facilityModelObject->name?$facilityModelObject->name:"";
				}						
					$unitName = $modelData->applicationunit->name;
					if($modelData->applicationunit->unit_type==1)
					{
						$unitName = $modelData->applicationaddress?$modelData->applicationaddress->unit_name:"";
					}
					$data['unit_id_label']=$unitName;
					$data['buyer_id_label']=$modelData->buyer->name;
					//$data['consignee_id_label']=$modelData->consignee->name;

					$data['app_id']=$modelData->app_id;
					$data['unit_id']=$modelData->unit_id;
					$data['buyer_id']=$modelData->buyer_id;
					$data['brand_consent']=$modelData->is_brand_consent;
					//$data['consignee_id']=$modelData->consignee_id;

					$data['brand_id']=isset($modelData->tcbrandconsent->brand_id)?$modelData->tcbrandconsent->brand_id:'';
					$data['brand_name']=isset($modelData->tcbrandconsent->brand->name)?$modelData->tcbrandconsent->brand->name:"NA";
					$data['brand_group']=isset($modelData->tcbrandconsent->brand->brandgroup->name)?$modelData->tcbrandconsent->brand->brandgroup->name:'NA';


					$data['tc_number']=($modelData->arrEnumStatus['approved']==$modelData->status || $modelData->arrEnumStatus['withdrawn']==$modelData->status ? $modelData->tc_number : 'TEMP'.$modelData->id);
					$data['tc_number_cds']=$modelData->tc_number_cds;

					$data['country_of_dispach']=$modelData->country_of_dispach;
					$data['country_of_dispach_name']=$modelData->country->name;
					//$data['country_of_destination']=$modelData->country_of_destination;
					
					$data['no_of_product']=is_array($modelData->product)?count($modelData->product):0;
					
					$data['purchase_order_number']=$modelData->purchase_order_number;	
					$data['grand_total_net_weight']=$modelData->grand_total_net_weight;	
					$data['grand_total_used_weight']=$modelData->grand_total_used_weight;
					
					$data['total_net_weight']=$modelData->total_net_weight;
					
					$data['status']=$modelData->status;
					$data['status_label']=$modelData->arrStatus[$modelData->status];
					
					$invoiceOptionArray=$modelData->arrInvoiceOptions;
					if(isset($post['type']) && $post['type'] =='2')
					{
						$invoiceOptionArray=$modelData->arrInvoiceOptionsLabel;
					}
					
					$data['payment_status_label']=($modelData->invoice_status!='' && $modelData->invoice_status>0)?$invoiceOptionArray[$modelData->invoice_status]:'NA';
					
					$data['invoice_status']=$modelData->invoice_status;
					$data['created_at']=$modelData->submit_to_oss_at?date($date_format,strtotime($modelData->submit_to_oss_at)):'NA';				
					//$data['created_at']=date($date_format,$modelData->created_at);
					if($modelData->invoice_type==1 && $modelData->fasttrack_addtional_charges==1)
					{
						$data['invoice_type']=$modelData->arrEnumTCInvoices['fasttrack'];
						$data['invoice_type_label']=$modelData->arrTCInvoices[$modelData->invoice_type];
					}else{
						$data['invoice_type']=$modelData->arrEnumTCInvoices['normal'];
						$data['invoice_type_label']=$modelData->arrTCInvoices[2];
					}
                    
					$data['invoice_type_color']=$modelData->arrStatusColor[8];
                    $data['sel_fasttrack_addt']=$modelData->fasttrack_addtional_charges;
					//$data['created_at']=$modelData->submit_to_oss_at?date($date_format,strtotime($modelData->submit_to_oss_at)):'NA';				
					//$data['created_at']=date($date_format,$modelData->created_at);

					if($modelData->invoice_type==$modelData->arrEnumTCInvoices['fasttrack'])
					{
						$data['fasttrack_created_at'] = $modelData->created_at;
						$data['fasttrack_due'] = strtotime('+1 day', $modelData->created_at);
					}
					$showedit= $this->canEditTc($modelData);
					$showdelete= $this->canDeleteTc($modelData);
					$showcopy= $this->canCopyTc($modelData);

					$data['showedit']= $showedit;
					$data['showdelete']= $showdelete;
					$data['showcopy']= $showcopy;
					
					$data['oss_label'] = $usermodel->ossnumberdetail($modelData->application->franchise_id);
					
					$customeroffernumber = $modelData->application->customer->customer_number;
					$TransactionCertificateNo='';					
					$draftText='';
					if($modelData->status!=$modelData->arrEnumStatus['approved'])
					{
						$draftText='DRAFT ';
						$TransactionCertificateNo=$modelData->id;
					}else{
						$TransactionCertificateNo=$modelData->tc_number;
					}					
					$tcFileName = 'TRANSACTION_CERTIFICATE_'.$customeroffernumber.'_'.$TransactionCertificateNo.'.pdf';
					$data['tc_filename'] = $tcFileName;

					$showpdf=0;
					if($modelData->status >= $modelData->arrEnumStatus['draft'] &&  $modelData->status!=$modelData->arrEnumStatus['rejected'] ){
						$showpdf = 1;
					}
					$data['showpdf']= $showpdf;
			
					$standardIds = [];
					$standardLabels = [];
					$standardCodeLabels = [];
					if(count($modelData->standard)>0){
						foreach($modelData->standard as $reqstandard)
						{
							$standardIds[] =  $reqstandard->standard_id;
							$standardLabels[] =  $reqstandard->standard->name;
							$standardCodeLabels[] =  $reqstandard->standard->code;
						}
					}
					$data["standard_id"]=$standardIds;	
					$data["standard_id_label"]=implode(', ',$standardLabels);
					$data["standard_id_code_label"]=implode(', ',$standardCodeLabels);	
					//$data["certification_body_id_label"]=$modelData->certificationbody->name;
					
					$data['approved_date']='NA';
					if($modelData->currentreviewercmt)
					{
						$data['approved_date']=date($date_format,$modelData->currentreviewercmt->created_at);	
					}
					

					$list[]=$data;
				}
			}
		}
		return ['request'=>$list,'total'=>$totalCount];
	}

	public function actionChangeInvoiceStatus()
    {
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$post = yii::$app->request->post();	
		$data = $post['data'];	
		$modelObj = new Request();		
		if($data)
		{
			if(!Yii::$app->userrole->hasRights(['generate_tc_bill'])){
				return false;
			}
			
			$dataids = [];
			if(count($data)>0){
				foreach($data as $tclist){
					if($tclist['value'] == '3'){
						$dataids[] = $tclist['id'];
					}else{
						$Request = Request::find()->where(['id'=> $tclist['id']])->one();
						$Request->invoice_status=$tclist['value'];
						$Request->save();
					}
				}
			}
			//$dataids = array_column($data, 'id');
			$applicationInvoice = [];
			if(count($dataids)>0){
				$model = Request::find()->where(['id'=> $dataids])->all();
				$applicationIds = [];
				foreach($model as $dataval)
				{
					$app_id = $dataval->app_id;
					$franchise_id = $dataval->application->franchise_id;
					$customer_id = $dataval->application->customer_id;
					$invoice_type = $dataval->invoice_type;
					if(!in_array($app_id,$applicationIds)){
						$applicationInvoice[$app_id] = [
							'app_id' => $app_id,
							'franchise_id' => $franchise_id,
							'customer_id' => $customer_id,
							'franchise_amount' => 0,
							'customer_amount' => 0,
							'standards' => [],
							'tc_request_ids' => [],
							'tc_request_numbers' => [],
							'domestic_single' => 0,
							'domestic_multiple' => 0,
							'export_single' => 0,
							'export_multiple' => 0,
							'domestic_single_amount' => 0,
							'domestic_multiple_amount' => 0,
							'export_single_amount' => 0,
							'export_multiple_amount' => 0,
							'franchise_single_amount' => 0,
							'franchise_multiple_amount' => 0,
                            'export_multiple_tc_nos' => [],
							'export_single_tc_nos' => [],
							'domestic_multiple_tc_nos' => [],
							'domestic_single_tc_nos' => [],
							'fasttrack_domestic_single' => 0,
							'fasttrack_domestic_multiple' => 0,
							'fasttrack_export_single' => 0,
							'fasttrack_export_multiple' => 0,
							'fasttrack_domestic_single_amount' => 0,
							'fasttrack_domestic_multiple_amount' => 0,
							'fasttrack_export_single_amount' => 0,
							'fasttrack_export_multiple_amount' => 0,
							'fasttrack_export_multiple_tc_nos' => [],
							'fasttrack_export_single_tc_nos' => [],
							'fasttrack_domestic_multiple_tc_nos' => [],
							'fasttrack_domestic_single_tc_nos' => [],
							'fasttrack_franchise_single_amount' => 0,
							'fasttrack_franchise_multiple_amount' => 0,
						];
						$applicationIds[] = $app_id;
					}
					//$appkey = array_search($app_id, array_column($applicationIds, 'app_id'));


					//
					$customerinvoiceamount = 0;
					$ossinvoiceamount = 0;
					$arrStd = [];
					$arrStdCodes = [];
					if(count($dataval->standard)>0){
						foreach($dataval->standard as $rstandard){
							$arrStd[] = $rstandard->standard_id;
							$arrStdCodes[] = $rstandard->standard->code;
						}						
					}
					sort($arrStd);
					
					$applicationInvoice[$app_id]['standards'] = array_merge($applicationInvoice[$app_id]['standards'] , $arrStd);
					$applicationInvoice[$app_id]['tc_request_ids'][] = $dataval->id;
					$applicationInvoice[$app_id]['tc_request_numbers'][] = $dataval->tc_number;
					//
					$connection = Yii::$app->getDb();
					$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();
					$command = $connection->createCommand("SELECT * FROM tbl_tc_request_product WHERE tc_request_id='".$dataval->id."' GROUP By invoice_no");
					$resultmulinv = $command->queryAll();

					$customerinvoicetype='export'; //Default to export if there is no match between dispatch and consignee country
					$consignee_countries = [];
					$country_of_dispach = $dataval->country_of_dispach;
					$requestProducts = RequestProduct::find()->where(['tc_request_id'=>$dataval->id])->all();
					if(count($requestProducts)>0)
					{
						foreach($requestProducts as $requestProduct)
						{
							$consignee_countries[] = $requestProduct->consignee->country_id;
							//$prdConsigneeCountry = ($prdConsignee->country
						}
					}
					$unique_consignee_countries = array_unique($consignee_countries);
					if((count($unique_consignee_countries)==1 && ! in_array($country_of_dispach,$unique_consignee_countries)) || count($unique_consignee_countries)>1){ 
						// If more than 1 country
						$customerinvoicetype = 'export';
						if($invoice_type==1){
							if(count($resultmulinv)>1){
								$applicationInvoice[$app_id]['fasttrack_export_multiple'] = $applicationInvoice[$app_id]['fasttrack_export_multiple'] + 1;
	
								if(! in_array($dataval,$applicationInvoice[$app_id]['fasttrack_export_multiple_tc_nos'])){
									$applicationInvoice[$app_id]['fasttrack_export_multiple_tc_nos'][] = $dataval->tc_number;
								}
							}else{
								$applicationInvoice[$app_id]['fasttrack_export_single'] = $applicationInvoice[$app_id]['fasttrack_export_single'] + 1;
	
								if(! in_array($dataval,$applicationInvoice[$app_id]['fasttrack_export_single_tc_nos'])){
									$applicationInvoice[$app_id]['fasttrack_export_single_tc_nos'][] = $dataval->tc_number;
								}
							}
						}else{
						if(count($resultmulinv)>1){
							$applicationInvoice[$app_id]['export_multiple'] = $applicationInvoice[$app_id]['export_multiple'] + 1;

                            if(! in_array($dataval,$applicationInvoice[$app_id]['export_multiple_tc_nos'])){
								$applicationInvoice[$app_id]['export_multiple_tc_nos'][] = $dataval->tc_number;
							}
						}else{
							$applicationInvoice[$app_id]['export_single'] = $applicationInvoice[$app_id]['export_single'] + 1;

                            if(! in_array($dataval,$applicationInvoice[$app_id]['export_single_tc_nos'])){
								$applicationInvoice[$app_id]['export_single_tc_nos'][] = $dataval->tc_number;
							}
						}
					}

					}else if(count($unique_consignee_countries)==1 && in_array($country_of_dispach,$unique_consignee_countries)){ 
						// If single country with dispatch and consignee country are same
						$customerinvoicetype = 'domestic';
						if($invoice_type==1){
							if(count($resultmulinv)>1){
								$applicationInvoice[$app_id]['fasttrack_domestic_multiple'] = $applicationInvoice[$app_id]['fasttrack_domestic_multiple'] + 1;
	
								if(! in_array($dataval,$applicationInvoice[$app_id]['fasttrack_domestic_multiple_tc_nos'])){
									$applicationInvoice[$app_id]['fasttrack_domestic_multiple_tc_nos'][] = $dataval->tc_number;
								}
							}else{
								$applicationInvoice[$app_id]['fasttrack_domestic_single'] = $applicationInvoice[$app_id]['fasttrack_domestic_single'] + 1;
	
								if(! in_array($dataval,$applicationInvoice[$app_id]['fasttrack_domestic_single_tc_nos'])){
									$applicationInvoice[$app_id]['fasttrack_domestic_single_tc_nos'][] = $dataval->tc_number;
								}
							}
						}else{
						if(count($resultmulinv)>1){
							$applicationInvoice[$app_id]['domestic_multiple'] = $applicationInvoice[$app_id]['domestic_multiple'] + 1;

                            if(! in_array($dataval,$applicationInvoice[$app_id]['domestic_multiple_tc_nos'])){
								$applicationInvoice[$app_id]['domestic_multiple_tc_nos'][] = $dataval->tc_number;
							}
						}else{
							$applicationInvoice[$app_id]['domestic_single'] = $applicationInvoice[$app_id]['domestic_single'] + 1;

                            if(! in_array($dataval,$applicationInvoice[$app_id]['domestic_single_tc_nos'])){
								$applicationInvoice[$app_id]['domestic_single_tc_nos'][] = $dataval->tc_number;
							}
						}
					}
				}
					$connection = Yii::$app->getDb();	
					$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();							
					$command = $connection->createCommand("SELECT comb.*,GROUP_CONCAT(combstd.standard_id ORDER BY combstd.standard_id ASC ) AS standardids FROM `tbl_certificate_tc_royalty_fee` AS comb INNER JOIN `tbl_certificate_tc_royalty_fee_cs` AS combstd ON comb.id=combstd.certificate_tc_royalty_fee_id WHERE comb.franchise_id='".$franchise_id."' and status=0 GROUP BY comb.id HAVING standardids ='".implode(',',$arrStd)."'");
					$result = $command->queryOne();
					if($result !== false)
					{
						if($invoice_type==1){
							if(count($resultmulinv)>1){
								//$applicationInvoice[$app_id]['customer_amount'] += $result['multiple_invoice_fee_for_oss_to_customer'];
								$applicationInvoice[$app_id]['customer_amount'] += count($dataval->productgroup) * $result['fasttrack_multiple_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
								$applicationInvoice[$app_id]['franchise_amount'] += count($dataval->productgroup) * $result['fasttrack_multiple_invoice_fee_for_hq_to_oss'];
								$applicationInvoice[$app_id]['fasttrack_franchise_multiple_amount'] += count($dataval->productgroup) * $result['fasttrack_multiple_invoice_fee_for_hq_to_oss'];
	
								if($customerinvoicetype=='export'){
									$applicationInvoice[$app_id]['fasttrack_export_multiple_amount'] += count($dataval->productgroup) * $result['fasttrack_multiple_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
								}else{
									$applicationInvoice[$app_id]['fasttrack_domestic_multiple_amount'] += count($dataval->productgroup) * $result['fasttrack_multiple_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
								}
							}else{
								//$applicationInvoice[$app_id]['customer_amount'] += $result['single_invoice_fee_for_oss_to_customer'];
								$applicationInvoice[$app_id]['customer_amount'] += $result['fasttrack_single_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
								$applicationInvoice[$app_id]['franchise_amount'] += $result['fasttrack_single_invoice_fee_for_hq_to_oss'];
								$applicationInvoice[$app_id]['fasttrack_franchise_single_amount'] += $result['fasttrack_single_invoice_fee_for_hq_to_oss'];
	
								if($customerinvoicetype=='export'){
									$applicationInvoice[$app_id]['fasttrack_export_single_amount'] += $result['fasttrack_single_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
								}else{
									$applicationInvoice[$app_id]['fasttrack_domestic_single_amount'] += $result['fasttrack_single_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
								}
							}
						}else {
						if(count($resultmulinv)>1){
							//$applicationInvoice[$app_id]['customer_amount'] += $result['multiple_invoice_fee_for_oss_to_customer'];
							$applicationInvoice[$app_id]['customer_amount'] += count($dataval->productgroup) * $result['multiple_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
							$applicationInvoice[$app_id]['franchise_amount'] += count($dataval->productgroup) * $result['multiple_invoice_fee_for_hq_to_oss'];
							$applicationInvoice[$app_id]['franchise_multiple_amount'] += count($dataval->productgroup) * $result['multiple_invoice_fee_for_hq_to_oss'];

							if($customerinvoicetype=='export'){
								$applicationInvoice[$app_id]['export_multiple_amount'] += count($dataval->productgroup) * $result['multiple_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
							}else{
								$applicationInvoice[$app_id]['domestic_multiple_amount'] += count($dataval->productgroup) * $result['multiple_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
							}
						}else{
							//$applicationInvoice[$app_id]['customer_amount'] += $result['single_invoice_fee_for_oss_to_customer'];
							$applicationInvoice[$app_id]['customer_amount'] += $result['single_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
							$applicationInvoice[$app_id]['franchise_amount'] += $result['single_invoice_fee_for_hq_to_oss'];
							$applicationInvoice[$app_id]['franchise_single_amount'] += $result['single_invoice_fee_for_hq_to_oss'];

							if($customerinvoicetype=='export'){
								$applicationInvoice[$app_id]['export_single_amount'] += $result['single_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
							}else{
								$applicationInvoice[$app_id]['domestic_single_amount'] += $result['single_'.$customerinvoicetype.'_invoice_fee_for_oss_to_customer'];
							}
						}
					}
				}

					
					
					//$model = Request::find()->Where(['id'=> $dataval['id']])->one();
					//if($model !== null)
					//{
						//$dataval->invoice_status=$dataval['value'];
						//$dataval->save();
					//}
				}
				$this->generateInvoice($applicationInvoice,$applicationIds,'customer'); // for customer
				$this->generateInvoice($applicationInvoice,$applicationIds,'franchise'); // for franchise
				if(count($dataids)>0){
					foreach($dataids as $updateid){
						$updateRequest = Request::find()->where(['id'=>$updateid])->one();
						if($updateRequest!==null){
							$updateRequest->invoice_status = $updateRequest->arrEnumInvoiceOptions['to_bill'];
							$updateRequest->save();
						}
					}
				}
				//Request::find()->updateAll(array('invoice_status' => 3), '  = 1 AND status = 0');
			}
			
			$responsedata=array('status'=>1,'message'=>'Saved Successfully!');
			
		}
		return $this->asJson($responsedata);
	}

	private function generateInvoice($applicationInvoice,$applicationIds,$invoice_for){
		if(count($applicationInvoice)>0){
			foreach($applicationInvoice as $appid => $appinvoice){
				
				$ospid = $appinvoice['franchise_id'];
				$franchiseID = $appinvoice['franchise_id'];

				$franchise = User::find()->where(['id'=>$franchiseID])->one();
				$invoiceCount = 0;
				$connection = Yii::$app->getDb();	
				$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();
		

				$command = $connection->createCommand("SELECT COUNT(invoice.id) AS invoice_count FROM `tbl_invoice` AS invoice
				INNER JOIN `tbl_application` AS app ON app.id = invoice.app_id AND app.franchise_id='$ospid' 
				GROUP BY app.franchise_id");
				$result = $command->queryOne();
				if($result  !== false)
				{
					$invoiceCount = $result['invoice_count'];
				}

				$maxid = $invoiceCount+1;
				if(strlen($maxid)=='1')
				{
					$maxid = "0".$maxid;
				}
				$invoicecode = "SY-".$franchise->usercompanyinfo->osp_number."-".$maxid."/".date("Y");
										
				$invoicemodel=new Invoice();	
				$invoicemodel->app_id=$appinvoice['app_id'];
				$invoicemodel->invoice_number=$invoicecode;
				$invoicemodel->franchise_id=$ospid;
				$invoicemodel->customer_id=$appinvoice['customer_id'];
				
				//$offerlist->conversion_currency_code ? $offerlist->conversion_currency_code : $offerlist->currency;	

				//New Code oct 30,2020 starts
				$invoicemodel->currency_code= 'USD';
				
				
				//$invoicemodel->conversion_currency_code = 'USD';
				//$invoicemodel->conversion_currency = 'USD';
				//$invoicemodel->conversion_rate = '1';



				$model = Application::find()->where(['id'=> $appid])->one();

				$total_amount = 0;
				if($invoice_for == 'customer'){
					$franchisesamestate = 0;
					if($model->applicationaddress && $franchise->usercompanyinfo && $model->applicationaddress->state_id == $franchise->usercompanyinfo->company_state_id){
						$franchisesamestate = 1;
					}
					$total_amount = $appinvoice['customer_amount'];
					$invoicemodel->invoice_type=1;
					
				}else{
					$total_amount = $appinvoice['franchise_amount'];
					$invoicemodel->invoice_type=2;
					
				}
				//$invoicemodel->invoice_due = date('Y-m-d',strtotime("+1 month"));
				
				$totalmdctaxpercentArr=0;
				$mdctaxArr=array();
				$mdctaxpercentArr=array();
				if($model->applicationaddress){
					$appmdc = Mandaycost::find()->where(['country_id' => $model->applicationaddress->country_id])->one();
					if($appmdc!==null)
					{
						if($invoice_for == 'customer'){
							if($franchisesamestate){
								$mandaycosttaxobj = $appmdc->mandaycosttax;
							}else{
								$mandaycosttaxobj = $appmdc->mandaycosttaxotherstate;
							}
						}else{
							$appmandaycost=$appmdc->man_day_cost;
							$mandaycosttaxobj = $appmdc->mandaycosttax;
						}
						
						if(is_array($mandaycosttaxobj) && count($mandaycosttaxobj)>0)
						{
							foreach($mandaycosttaxobj as $val)
							{
								$mdctaxArr[]=$val->tax_name;
								$mdctaxpercentArr[]=$val->tax_percentage;						
							}
						}elseif($mandaycosttaxobj!==null && $mandaycosttaxobj){
							$mdctaxArr[]=$mandaycosttaxobj->tax_name;
							$mdctaxpercentArr[]=$mandaycosttaxobj->tax_percentage;	
						}
					}
	
					$totalmdctaxpercentArr=array_sum($mdctaxpercentArr);
					//$resultarr["offer_currency_code"]=$appmdc->currency_code;
					
					//$resultarr["tax_percentage"]=$mdctaxpercentArr;
				}
				
				$invoicemodel->conversion_total_fee = $total_amount;
				$invoicemodel->total_fee=$total_amount;
				$invoicemodel->discount= 0;
				$invoicemodel->invoice_from = 2;

				if($invoicemodel->save())
				{
					$invoiceID = $invoicemodel->id;
					$tax_percentage=0;
					$royalBasedTotalTaxAmount = 0;
					if(count($mdctaxArr)>0)
					{
						
						foreach($mdctaxArr as $indexkey=>$ofrT)
						{
							$tax_percentage=$mdctaxpercentArr[$indexkey];
							
							$royalBasedTaxAmount=0;
							$royalBasedTaxAmount=($total_amount*$tax_percentage/100);
							$royalBasedTotalTaxAmount = $royalBasedTotalTaxAmount+$royalBasedTaxAmount;	
							
							$invoiceListTax=new InvoiceTax();
							$invoiceListTax->invoice_id=$invoiceID;							
							$invoiceListTax->tax_name=$ofrT;	
							$invoiceListTax->tax_percentage=$tax_percentage;
							$invoiceListTax->amount=$royalBasedTaxAmount;							
							$invoiceListTax->save();
						}							
					}

					$grand_total = $total_amount + $royalBasedTotalTaxAmount;
					$invoicemodel->conversion_tax_amount = $royalBasedTotalTaxAmount;
					$invoicemodel->conversion_total_payable_amount = $grand_total;
					$invoicemodel->conversion_required_status= 1;
					$invoicemodel->grand_total_fee=$grand_total;
					$invoicemodel->tax_amount=$royalBasedTotalTaxAmount;
					$invoicemodel->tax_percentage=$totalmdctaxpercentArr;
					$invoicemodel->total_payable_amount=$grand_total;
					$invoicemodel->no_of_tc = count($appinvoice['tc_request_ids']);

					if($invoice_for == 'customer'){
						$invoicemodel->export_single_amount = $appinvoice['export_single_amount'];
						$invoicemodel->export_multiple_amount = $appinvoice['export_multiple_amount'];
						$invoicemodel->domestic_single_amount = $appinvoice['domestic_single_amount'];
						$invoicemodel->domestic_multiple_amount = $appinvoice['domestic_multiple_amount'];
						$invoicemodel->fasttrack_export_single_amount = $appinvoice['fasttrack_export_single_amount'];
						$invoicemodel->fasttrack_export_multiple_amount = $appinvoice['fasttrack_export_multiple_amount'];
						$invoicemodel->fasttrack_domestic_single_amount = $appinvoice['fasttrack_domestic_single_amount'];
						$invoicemodel->fasttrack_domestic_multiple_amount = $appinvoice['fasttrack_domestic_multiple_amount'];
					}else{
						$invoicemodel->franchise_single_amount = $appinvoice['franchise_single_amount'];
						$invoicemodel->franchise_multiple_amount = $appinvoice['franchise_multiple_amount'];
						$invoicemodel->fasttrack_franchise_single_amount = $appinvoice['fasttrack_franchise_single_amount'];
						$invoicemodel->fasttrack_franchise_multiple_amount = $appinvoice['fasttrack_franchise_multiple_amount'];
					}
					
					$invoicemodel->save();
					
					//if($type==2)
					//{
						// ---- Store the Application Unit Standard in Invoice Code Start Here -------
												
						// ---- Store the Application Standard in Invoice Code End Here -------	
						
					//if($type==2)
					//{				
						// echo $appinvoice['export_single'].' '.$appinvoice['export_multiple'].' '.$appinvoice['domestic_single'].' '.$appinvoice['domestic_multiple'];
						if($invoice_for == 'customer'){
							if($appinvoice['export_single']>0){
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='TC Fees (Export-Single TCs)';
								$invoiceDetailsModel->description='TC making charges for '.$appinvoice['export_single'].' Nos Export Single TCs ('.implode(', ',$appinvoice['export_single_tc_nos']).')';
								$invoiceDetailsModel->amount=$appinvoice['export_single_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
								 
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
							if($appinvoice['export_multiple']>0){
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='TC Fees (Export-Multiple TCs)';
								$invoiceDetailsModel->description='TC making charges for '.$appinvoice['export_multiple'].' Nos Export Multiple TCs ('.implode(', ',$appinvoice['export_multiple_tc_nos']).')';
								$invoiceDetailsModel->amount=$appinvoice['export_multiple_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
	
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
							if($appinvoice['domestic_single']>0){
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='TC Fees (Domestic-Single TCs)';
								$invoiceDetailsModel->description='TC making charges for '.$appinvoice['domestic_single'].' Nos Domestic Single TCs ('.implode(', ',$appinvoice['domestic_single_tc_nos']).')';
								$invoiceDetailsModel->amount=$appinvoice['domestic_single_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
	
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
							if($appinvoice['domestic_multiple']>0){
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='TC Fees (Domestic-Multiple TCs)';
								$invoiceDetailsModel->description='TC making charges for '.$appinvoice['domestic_multiple'].' Nos Domestic Multiple TCs ('.implode(', ',$appinvoice['domestic_multiple_tc_nos']).')';
								$invoiceDetailsModel->amount=$appinvoice['domestic_multiple_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
							if($appinvoice['fasttrack_domestic_multiple']>0){
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='Fasttrack TC Fees (Domestic-Multiple TCs)';
								$invoiceDetailsModel->description='Fasttrack TC making charges for '.$appinvoice['fasttrack_domestic_multiple'].' Nos Domestic Multiple TCs ('.implode(', ',$appinvoice['fasttrack_domestic_multiple_tc_nos']).')';
								$invoiceDetailsModel->amount=$appinvoice['fasttrack_domestic_multiple_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
	
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
							if($appinvoice['fasttrack_domestic_single']>0){
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='Fasttrack Fees (Domestic-Single TCs)';
								$invoiceDetailsModel->description='Fasttrack TC making charges for '.$appinvoice['fasttrack_domestic_single'].' Nos Domestic Single TCs ('.implode(', ',$appinvoice['fasttrack_domestic_single_tc_nos']).')';
								$invoiceDetailsModel->amount=$appinvoice['fasttrack_domestic_single_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
	
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
							if($appinvoice['fasttrack_export_single']>0){
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='Fasttrack TC Fees (Export-Single TCs)';
								$invoiceDetailsModel->description='Fasttrack TC making charges for '.$appinvoice['fasttrack_export_single'].' Nos Export Single TCs ('.implode(', ',$appinvoice['fasttrack_export_single_tc_nos']).')';
								$invoiceDetailsModel->amount=$appinvoice['fasttrack_export_single_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
	
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
							if($appinvoice['fasttrack_export_multiple']>0){
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='Fasttrack TC Fees (Export-Multiple TCs)';
								$invoiceDetailsModel->description='Fasttrack TC making charges for '.$appinvoice['fasttrack_export_multiple'].' Nos Export Multiple TCs ('.implode(', ',$appinvoice['fasttrack_export_multiple_tc_nos']).')';
								$invoiceDetailsModel->amount=$appinvoice['fasttrack_export_multiple_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
	
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
						}else{
							if($appinvoice['franchise_multiple_amount']>0){
								$multiple_tc_nos = implode(', ', array_merge($appinvoice['export_multiple_tc_nos'],$appinvoice['domestic_multiple_tc_nos']));
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='TC Fees (Multiple TCs)';
								$invoiceDetailsModel->description='TC making charges for '.((int)$appinvoice['domestic_multiple'] + (int)$appinvoice['export_multiple'] ).' Nos Multiple TCs ('.$multiple_tc_nos.')';
								$invoiceDetailsModel->amount=$appinvoice['franchise_multiple_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
	
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
							if($appinvoice['franchise_single_amount']>0){
								$single_tc_nos = implode(', ', array_merge($appinvoice['export_single_tc_nos'],$appinvoice['domestic_single_tc_nos']));
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='TC Fees (Single TCs)';
								$invoiceDetailsModel->description='TC making charges for '.((int)$appinvoice['domestic_single'] + (int)$appinvoice['export_single'] ).' Nos Single TCs ('.$single_tc_nos.')';
								$invoiceDetailsModel->amount=$appinvoice['franchise_single_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
							if($appinvoice['fasttrack_franchise_multiple_amount']>0){
								$multiple_tc_nos = implode(', ', array_merge($appinvoice['fasttrack_export_multiple_tc_nos'],$appinvoice['fasttrack_domestic_multiple_tc_nos']));
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='Fastrack TC Fees (Multiple TCs)';
								$invoiceDetailsModel->description='Fastrack TC making charges for '.((int)$appinvoice['fasttrack_domestic_multiple'] + (int)$appinvoice['fasttrack_export_multiple']).' Nos Multiple TCs ('.$multiple_tc_nos.')';
								$invoiceDetailsModel->amount=$appinvoice['fasttrack_franchise_multiple_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
	
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
							if($appinvoice['fasttrack_franchise_single_amount']>0){
								$single_tc_nos = implode(', ', array_merge($appinvoice['fasttrack_export_single_tc_nos'],$appinvoice['fasttrack_domestic_single_tc_nos']));
								$invoiceDetailsModel=new InvoiceDetails();
								$invoiceDetailsModel->invoice_id=$invoiceID;
								$invoiceDetailsModel->activity='Fasttrack TC Fees (Single TCs)';
								$invoiceDetailsModel->description='Fasttrack TC making charges for '.((int)$appinvoice['fasttrack_domestic_single'] + (int)$appinvoice['fasttrack_export_single']).' Nos Single TCs ('.$single_tc_nos.')';
								$invoiceDetailsModel->amount=$appinvoice['fasttrack_franchise_single_amount'];
								$invoiceDetailsModel->type='1';	                             								
								$invoiceDetailsModel->entry_type=0;
								$invoiceDetailsModel->save();
	
								if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
									$appinvstandards = array_unique($appinvoice['standards']);
									foreach($appinvstandards as $tcstandardid)
									{
										$invoiceDetailsStdModel=new InvoiceDetailsStandard();
										$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
										$invoiceDetailsStdModel->standard_id=$tcstandardid;
										$invoiceDetailsStdModel->save();
									}
								}
							}
						}
						

						// $invoiceDetailsModel=new InvoiceDetails();
						// $invoiceDetailsModel->invoice_id=$invoiceID;
						// $invoiceDetailsModel->activity='TC Fees';
						// $invoiceDetailsModel->description='TC Fees for '.implode(', ',$appinvoice['tc_request_numbers']);
						// $invoiceDetailsModel->amount=$total_amount;
						// $invoiceDetailsModel->type='1';	                             								
						// $invoiceDetailsModel->entry_type=0;
						// $invoiceDetailsModel->save();

						

						// if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
						// 	$appinvstandards = array_unique($appinvoice['standards']);
						// 	foreach($appinvstandards as $tcstandardid)
						// 	{
						// 		$invoiceDetailsStdModel=new InvoiceDetailsStandard();
						// 		$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
						// 		$invoiceDetailsStdModel->standard_id=$tcstandardid;
						// 		$invoiceDetailsStdModel->save();
						// 	}
						// }

						if(isset($appinvoice['standards']) && count($appinvoice['standards']) > 0){
							$appinvstandards = array_unique($appinvoice['standards']);
							foreach($appinvstandards as $tcstandardid)
							{
								$InvoiceStandard=new InvoiceStandard();
								$InvoiceStandard->invoice_id=$invoiceID;
								$InvoiceStandard->standard_id=$tcstandardid;
								$InvoiceStandard->save();
							}
						}

						if(isset($appinvoice['tc_request_ids']) && count($appinvoice['tc_request_ids']) > 0){
							foreach($appinvoice['tc_request_ids'] as $tcindexkey => $tcrequestid)
							{
								$tc_number = isset($appinvoice['tc_request_numbers'][$tcindexkey])?$appinvoice['tc_request_numbers'][$tcindexkey]:'';
								//tc_request_numbers
								$InvoiceStandard=new InvoiceTc();
								$InvoiceStandard->invoice_id=$invoiceID;
								$InvoiceStandard->tc_request_id=$tcrequestid;
								$InvoiceStandard->tc_number=$tc_number;
								$InvoiceStandard->save();
							}
						}
						
						/*
						if(count($arrScopeHolderStandards)>0)
						{
							array_unique($arrScopeHolderStandards);
							foreach($arrScopeHolderStandards as $arrScopeHolderStd)
							{
								$invoiceDetailsStdModel=new InvoiceDetailsStandard();
								$invoiceDetailsStdModel->invoice_detail_id=$invoiceDetailsModel->id;
								$invoiceDetailsStdModel->standard_id=$arrScopeHolderStd;
								$invoiceDetailsStdModel->save();
							}
						}
						*/
					//}
					//}	
					// -------- Invoice to OSS Get the Royalty Fee based on the Standard Code End Here ----------
					 			
				}	
			}
		}
	}


	public function canCopyTc($modelData){
		$userrole = Yii::$app->userrole;		
		$userid=$userrole->user_id;				
		$user_type=$userrole->user_type;
		$role=$userrole->role;
		$rules=$userrole->rules;
		$franchiseid=$userrole->franchiseid;		
		$resource_access=$userrole->resource_access;
		$is_headquarters =$userrole->is_headquarters;
		$role_chkid=$userrole->role_chkid;
			
		$showcopy=0;
		if($modelData->status == $modelData->arrEnumStatus['rejected'] || $modelData->arrEnumStatus['withdrawn'] &&  ($user_type== 2 || $resource_access==1 || ($user_type== 1 &&  in_array('clone_tc_application',$rules)) )){
			if($resource_access==1){
				$showcopy = 1;
			}else if($user_type == 2){
				if($modelData->application->customer_id == $userid){
					$showcopy = 1;
				}	
			}else if($user_type == 1){
				if($is_headquarters !=1){
					if($modelData->application->franchise_id == $franchiseid){
						$showcopy = 1;
					}	
				}else{
					$showcopy = 1;
				}
			}
		}
		return $showcopy;
	}


	public function canDeleteTc($modelData){
		$userrole = Yii::$app->userrole;		
		$userid=$userrole->user_id;				
		$user_type=$userrole->user_type;
		$role=$userrole->role;
		$rules=$userrole->rules;
		$franchiseid=$userrole->franchiseid;		
		$resource_access=$userrole->resource_access;
		$is_headquarters =$userrole->is_headquarters;
		$role_chkid=$userrole->role_chkid;

		$showdelete = 0;
		if(($modelData->status == $modelData->arrEnumStatus['open'] || $modelData->status == $modelData->arrEnumStatus['draft'] || $modelData->status == $modelData->arrEnumStatus['rejected'] ) && ($user_type==2 || $resource_access==1 || ($user_type== 1 &&  in_array('delete_tc_application',$rules)) )){
			if($resource_access==1){
				$showdelete = 1;
			}else if($user_type == 2){
				if($modelData->application->customer_id == $userid){
					$showdelete = 1;
				}	
			}else if($user_type == 1){
				if($is_headquarters !=1){
					if($modelData->application->franchise_id == $franchiseid){
						$showedit = 1;
					}	
				}else{
					$showedit = 1;
				}
			}
		}
		return $showdelete;
	}

	public function canAddTc($app_id){
		$userrole = Yii::$app->userrole;
		$userid=$userrole->user_id;				
		$user_type=$userrole->user_type;
		$role=$userrole->role;
		$rules=$userrole->rules;
		$franchiseid=$userrole->franchiseid;		
		$resource_access=$userrole->resource_access;
		$is_headquarters =$userrole->is_headquarters;
		$role_chkid=$userrole->role_chkid;

		$adddata = 0;
		if($user_type==2 || $resource_access==1 || ($user_type== 1 &&  in_array('add_tc_application',$rules))){
			
			if($resource_access==1){
				$adddata = 1;
			}else{
				$Application = Application::find()->where(['id'=>$app_id])->one();
				if($Application !== null){
					if($user_type == 2){
						if($Application->customer_id == $userid){
							$adddata = 1;
						}	
					}else if($user_type == 1){
						if($is_headquarters !=1){
							if($Application->franchise_id == $franchiseid){
								$adddata = 1;
							}	
						}else{
							$adddata = 1;
						}
					}
				}
			}
			

		}
		return $adddata;
	}	

	public function canEditTc($modelData){		
		$userrole = Yii::$app->userrole;
		$userid=$userrole->user_id;				
		$user_type=$userrole->user_type;
		$role=$userrole->role;
		$rules=$userrole->rules;
		$franchiseid=$userrole->franchiseid;		
		$resource_access=$userrole->resource_access;
		$is_headquarters =$userrole->is_headquarters;
		$role_chkid=$userrole->role_chkid;		
		
		$showedit = 0;
		if(($modelData->status == $modelData->arrEnumStatus['open'] 
			|| $modelData->status == $modelData->arrEnumStatus['pending_with_customer'] 
			|| $modelData->status == $modelData->arrEnumStatus['draft'] )
			&& ($user_type==2 || $resource_access==1 || ($user_type== 1 &&  in_array('edit_tc_application',$rules)) )){
				if($resource_access==1){
					$showedit = 1;
				}else if($user_type == 2){
					if($modelData->application->customer_id == $userid){
						$showedit = 1;
					}	
				}else if($user_type == 1){
					if($is_headquarters == 1){
						$showedit = 1;
					}else if($franchiseid == $modelData->application->franchise_id){
						$showedit = 1;
					}
				}
		}
		if(($modelData->status == $modelData->arrEnumStatus['waiting_for_osp_review'] || $modelData->status == $modelData->arrEnumStatus['pending_with_osp']   ) && ($user_type==3 || $resource_access==1 || ($user_type== 1 &&  in_array('assign_as_oss_review_for_tc',$rules)) )){
			$showedit = 1;
			if($resource_access==1){
				$showedit = 1;
			}else{
				if($user_type ==3){
					if($resource_access ==5 && $modelData->application->franchise_id == $franchiseid){
						$showedit = 1;
					}else if($modelData->application->franchise_id==$userid)
					{
						$showedit = 1;
					}
				}else if($user_type == 1){
					if($is_headquarters !=1){
						if($modelData->application->franchise_id == $franchiseid){
							$showedit = 1;
						}	
					}else{
						$showedit = 1;
					}
				}
			}
			
		}
		if($modelData->status == $modelData->arrEnumStatus['review_in_process']){
			$Revieweruser_id = '';
			$RequestReviewer = RequestReviewer::find()->where(['tc_request_id'=>$modelData->id])->one();
			if($RequestReviewer!==null){
				$Revieweruser_id = $RequestReviewer->user_id;//4;
			}
			if($resource_access==1){
				$showedit = 1;
			}else if($user_type==1 && $Revieweruser_id == $userid){
				$showedit = 1;
			}

			
		}
		return $showedit;
	}
	public function actionGetAppdata()
	{
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$userrole = Yii::$app->userrole;
		$userid=$userrole->user_id;				
		$user_type=$userrole->user_type;
		$role=$userrole->role;
		$rules=$userrole->rules;
		$franchiseid=$userrole->franchiseid;		
		$resource_access=$userrole->resource_access;
		$is_headquarters =$userrole->is_headquarters;
		$role_chkid=$userrole->role_chkid;	
		
		$arrPaymentStatus=array();
		$requestModel = new Request();
		$arrInvoiceOptionsLabel = $requestModel->arrInvoiceOptionsLabel;
		if(count($arrInvoiceOptionsLabel)>0)
		{
			foreach($arrInvoiceOptionsLabel as $keyInv=>$valInv)
			{
				$arrPaymentStatus[]=array('id'=>$keyInv,'name'=>$valInv);
			}
		}
		
		$apparr = Yii::$app->globalfuns->getAppListForTC();
		$responsedata=array('status'=>1,'appdata'=>$apparr,'paymentStatus'=>$arrPaymentStatus);
		return $this->asJson($responsedata);
	}

	public function actionGetAppaddress()
	{
		$data = Yii::$app->request->post();
		if($data)
		{
			$model = ApplicationChangeAddress::find()->where(['current_app_id'=>$data['id']])->one();
			if($model!==null)
			{
				$appaddress = [];
				$appaddress['address'] = $model->address;
				$appaddress['city'] = $model->city;
				$appaddress['zipcode'] = $model->zipcode;
				$appaddress['country'] = $model->country->name;
				$appaddress['state'] = $model->state->name;
				return ['data'=>$appaddress];
			}			
		}	
	}

	public function actionGetAppfacilityaddress()
	{
		$data = Yii::$app->request->post();
		if($data)
		{
			$model = ApplicationUnit::find()->where(['app_id'=>$data['id'],'id'=>$data['facility_id']])->one();
			if($model!==null)
			{
				$appaddress = [];
				$appaddress['address'] = $model->address;
				$appaddress['city'] = $model->city;
				$appaddress['zipcode'] = $model->zipcode;
				$appaddress['country'] = $model->country->name;
				$appaddress['state'] = $model->state->name;
				return ['data'=>$appaddress];
			}				
		}	
	}

	public function actionGetUnitaddress()
	{
		$data = Yii::$app->request->post();		
		if($data)
		{
			$model = ApplicationUnit::find()->where(['id'=>$data['id']])->one();
			if($model!==null)
			{
				$appaddress = [];
				$appaddress['address'] = $model->address;
				$appaddress['city'] = $model->city;
				$appaddress['zipcode'] = $model->zipcode;
				$appaddress['country'] = $model->country->name;
				$appaddress['state'] = $model->state->name;
				return ['data'=>$appaddress];
			}
		}
	}

	public function actionGetBuyeraddress()
	{
		$data = Yii::$app->request->post();		
		if($data)
		{
			$model = Buyer::find()->where(['id'=>$data['id']])->one();
			if($model!==null)
			{
				$appaddress = [];
				$appaddress['address'] = $model->address;
				$appaddress['city'] = $model->city;
				$appaddress['zipcode'] = $model->zipcode;
				$appaddress['country'] = $model->country_id?$model->country->name:"";
				$appaddress['state'] = $model->state_id?$model->state->name:"";
				return ['data'=>$appaddress];
			}
		}
	}

	public function actionGetInspectionaddress()
	{
		$data = Yii::$app->request->post();		
		if($data)
		{
			$model = InspectionBody::find()->where(['id'=>$data['id']])->one();
			if($model!==null)
			{
				$appaddress = [];
				$appaddress['address'] = $model->description;
				return ['data'=>$appaddress];
			}
		}
	}

	public function actionGetAppunitdata()
	{
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');		
		$data = Yii::$app->request->post();
		if ($data) 
		{	
			$unitarr = Yii::$app->globalfuns->getAppunitdata($data);
			$responsedata=array('status'=>1,'unitdata'=>$unitarr);
		}
		return $this->asJson($responsedata);
	}

	public function actionGetAppstddata()
	{
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');		
		$data = Yii::$app->request->post();
		$standardarr = array();
		if ($data) 
		{	
			$unitarr = array();
			//$standardmodel = ApplicationStandard::find()->where(['app_id' => $data['id']])->all();
			$datas = ['unit_id'=> $data['id']];
			$standardarr = Yii::$app->globalfuns->getAppUnitStandards($datas);
			$responsedata=array('status'=>1,'stddata'=>$standardarr);
		}
		return $this->asJson($responsedata);
	}
	
	/*
	public function actionDownloadBlfile(){
		$data = Yii::$app->request->post();
		if($data && isset($data['id']))
		{
			$files = Request::find()->where(['id'=>$data['id']])->one();
			if($files!==null)
			{
				//if($data['filetype']=='gisfile'){
					$filename = $files->bl_copy;
				//}
				header('Access-Control-Allow-Origin: *');
				header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
				header('Access-Control-Max-Age: 1000');
				header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
				
				$filepath=Yii::$app->params['tc_files']."blcopy_files/".$filename;
				if(file_exists($filepath)) {
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
					header('Access-Control-Expose-Headers: Content-Length,Content-Disposition,filename,Content-Type;');
					header('Access-Control-Allow-Headers: Content-Length,Content-Disposition,filename,Content-Type');
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					header('Content-Length: ' . filesize($filepath));
					flush(); // Flush system output buffer
					readfile($filepath);
				}
			}	
		}	
		die;
	}
	*/

	public function actionDownloadevidencefile()
	{
		$data = Yii::$app->request->post();
		if($data)
		{
			$userrole = Yii::$app->userrole;
			$userid=$userrole->user_id;				
			$user_type=$userrole->user_type;
			$role=$userrole->role;
			$rules=$userrole->rules;
			$franchiseid=$userrole->franchiseid;		
			$resource_access=$userrole->resource_access;
			$is_headquarters =$userrole->is_headquarters;
			$role_chkid=$userrole->role_chkid;
			
			$filetype = $data['filetype'];
			if($filetype=='sales_invoice_with_packing_list'){
				$filetype = 'sales_invoice';
			}else if($filetype=='mass_balance_sheet'){
				$filetype = 'mass_balance';
			}
			
			$modelObj=new Request();

			$files = RequestEvidence::find()->alias('t')->where(['t.id'=>$data['id'],'t.evidence_type'=>$filetype ]);					
			if($resource_access != '1')
			{
				$files = $files->join('inner join', 'tbl_tc_request as req','req.id =t.tc_request_id');
				$files = $files->join('inner join', 'tbl_application as app','app.id =req.app_id');
			
				if($user_type== Yii::$app->params['user_type']['user'] && $is_headquarters!=1 ){
					$files = $files->andWhere(' app.franchise_id="'.$franchiseid.'" ');
				}
				
				if($user_type== Yii::$app->params['user_type']['customer']){
					$files = $files->andWhere('app.customer_id="'.$userid.'"');
				}else if($user_type== Yii::$app->params['user_type']['user'] && (in_array('add_tc_application',$rules) 
					|| in_array('edit_tc_application',$rules)
					|| in_array('delete_tc_application',$rules)  || in_array('view_tc_application',$rules)
					|| in_array('clone_tc_application',$rules)
					)
				){
				}else if($user_type== Yii::$app->params['user_type']['user'] && in_array('assign_as_oss_review_for_tc',$rules)){
					$files = $files->andWhere(' req.status>1 and app.franchise_id="'.$franchiseid.'" ');
				}else if($user_type== Yii::$app->params['user_type']['user'] && in_array('application_review',$rules)){
					$files = $files->join('left join', 'tbl_tc_request_reviewer as reqreviewer','reqreviewer.tc_request_id =req.id');
					$files = $files->andWhere('(req.status= "'.$modelObj->arrEnumStatus['waiting_for_review'].'" or reqreviewer.user_id="'.$userid.'")');
					//$files = $files->andWhere('(reqreviewer.user_id="'.$userid.'")');
				}else if($user_type== Yii::$app->params['user_type']['franchise']  && $is_headquarters!=1){
					if($resource_access == '5'){
						$userid = $franchiseid;
					}
					$files = $files->andWhere('req.status>1 and app.franchise_id="'.$userid.'"');
				}					
			}
			
			$files = $files->one();
			if($files!==null)
			{
				$filename = $files->evidence_file;
				
				/*
				if($data['filetype']=='sales_invoice_with_packing_list'){
					$filename = $files->sales_invoice_with_packing_list;
				}else if($data['filetype']=='transport_document'){
					$filename = $files->transport_document;
				}else if($data['filetype']=='mass_balance_sheet'){
					$filename = $files->mass_balance_sheet;
				}else if($data['filetype']=='test_report'){
					$filename = $files->test_report;
				}
				*/
				
				header('Access-Control-Allow-Origin: *');
				header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
				header('Access-Control-Max-Age: 1000');
				header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
				
				$filepath=Yii::$app->params['tc_files']."evidence_files/".$filename;
				if(file_exists($filepath)) 
				{
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
					header('Access-Control-Expose-Headers: Content-Length,Content-Disposition,filename,Content-Type;');
					header('Access-Control-Allow-Headers: Content-Length,Content-Disposition,filename,Content-Type');
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					header('Content-Length: ' . filesize($filepath));
					flush(); // Flush system output buffer
					readfile($filepath);
				}			
			}
		}	
		die;
	}
	
	public function actionDownloadFile()
    {
		$data = Yii::$app->request->post();
		if($data) 
		{
			$userrole = Yii::$app->userrole;
			$userid=$userrole->user_id;				
			$user_type=$userrole->user_type;
			$role=$userrole->role;
			$rules=$userrole->rules;
			$franchiseid=$userrole->franchiseid;		
			$resource_access=$userrole->resource_access;
			$is_headquarters =$userrole->is_headquarters;
			$role_chkid=$userrole->role_chkid;
			
			$date_format = Yii::$app->globalfuns->getSettings('date_format');
			$modelRequest = new Request();
										
			$html='';
			$model = Request::find()->alias('t')->where(['t.id' => $data['id']]);
			$model = $model->innerJoinWith(['application as app']);				
			if($resource_access != '1')
			{
				if($user_type== Yii::$app->params['user_type']['user'] && $is_headquarters!=1 ){
					$model = $model->andWhere(' app.franchise_id="'.$franchiseid.'" ');
				}
				
				if($user_type== Yii::$app->params['user_type']['customer']){
					$model = $model->andWhere('app.customer_id="'.$userid.'"');
				}else if($user_type== Yii::$app->params['user_type']['user'] && (in_array('add_tc_application',$rules) 
					|| in_array('edit_tc_application',$rules)
					|| in_array('delete_tc_application',$rules)  || in_array('view_tc_application',$rules)
					|| in_array('clone_tc_application',$rules)
					)
				){

				}else if($user_type== Yii::$app->params['user_type']['user'] && in_array('assign_as_oss_review_for_tc',$rules)){
					$model = $model->andWhere(' app.franchise_id="'.$franchiseid.'" ');
				}else if($user_type== Yii::$app->params['user_type']['user'] && in_array('application_review',$rules)){
					$model = $model->joinWith(['reviewer as reviewer']);	
					//$model = $model->andWhere('(reviewer.user_id="'.$userid.'")');
					$model = $model->andWhere('(t.status= "'.$modelRequest->arrEnumStatus['waiting_for_review'].'" or reviewer.user_id="'.$userid.'")');
				}else if($user_type== Yii::$app->params['user_type']['franchise']  && $is_headquarters!=1){
					if($resource_access == '5'){
						$userid = $franchiseid;
					}
					$model = $model->andWhere('app.franchise_id="'.$userid.'"');
				}					
			}			
			$model = $model->andWhere(['not in','t.status',array($modelRequest->arrEnumStatus['open'],$modelRequest->arrEnumStatus['rejected'])]);
			$model = $model->one();
			if($model !== null)
			{
				if($model->status==$modelRequest->arrEnumStatus['approved'])
				{
					$filename = $model->filename;
					header('Access-Control-Allow-Origin: *');
					header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
					header('Access-Control-Max-Age: 1000');
					header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

					
					$filepath=Yii::$app->params['tc_files']."tc/".$filename;
					if(file_exists($filepath)) {
						header('Content-Description: File Transfer');
						header('Content-Type: application/octet-stream');
						header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
						header('Access-Control-Expose-Headers: Content-Length,Content-Disposition,filename,Content-Type;');
						header('Access-Control-Allow-Headers: Content-Length,Content-Disposition,filename,Content-Type');
						header('Expires: 0');
						header('Cache-Control: must-revalidate');
						header('Pragma: public');
						header('Content-Length: ' . filesize($filepath));
						flush(); // Flush system output buffer
						readfile($filepath);
					}
					die;
				}else{	
					$this->generateTC($data['id'],false);
				}	
			}
		}
	}	

	public function actionCheckstandardcombination()
	{
		$modelRequest = new Request();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$data = Yii::$app->request->post();
		if ($data) 
		{	
			//$data = json_decode($datapost['formvalues'],true);
			
			$connection = Yii::$app->getDb();


			
			$standard_ids = $data['standard_id'];
			if(is_array($standard_ids) && count($standard_ids)>1)
			{
				$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();

				$StandardCombination = StandardCombination::find()->where(['status'=>0])->alias('t');
				$StandardCombination = $StandardCombination->join('inner join', 'tbl_standard_combination_standard as combination','t.id =combination.standard_combination_id');
				$StandardCombination = $StandardCombination->andWhere(['combination.standard_id'=>$standard_ids]);
				$StandardCombination = $StandardCombination->one();
				if($StandardCombination!==null){
					
					sort($standard_ids);

					$command = $connection->createCommand("select GROUP_CONCAT(combstd.standard_id order by combstd.standard_id asc ) as standardids from tbl_standard_combination as comb inner join tbl_standard_combination_standard as combstd on comb.id=combstd.standard_combination_id where combstd.standard_id in (".implode(',',$standard_ids).") GROUP BY comb.id HAVING standardids = '".implode(',',$standard_ids)."'");

					$result = $command->queryOne();
					if($result  === false)
					{
						return $responsedata=array('status'=>0,'message'=>["standard_id"=>["Standard Combination is not invalid"]]);
					}

					//return $responsedata=array('status'=>0,'message'=>'Found');
				}
			}
			$responsedata=array('status'=>1,'message'=>"Standard Combination is valid");
		}
		return $responsedata;
	}


    public function actionCreate()
	{
		$modelRequest = new Request();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$target_dir = Yii::$app->params['tc_files']."blcopy_files/"; 
		$datapost = Yii::$app->request->post();
		if ($datapost) 
		{	
			$userData = Yii::$app->userdata->getData();
			$data = json_decode($datapost['formvalues'],true);
			if ($data) 
			{
				if(!Yii::$app->userrole->isValidApplication($data['app_id']))
				{
					$responsedata=array('status'=>0,'message'=>'Application is not valid.');
					return $this->asJson($responsedata);
				}
				
				$editStatus=1;
				$existing_unitid = '';
				$existing_appid = '';
				$connection = Yii::$app->getDb();
				
				if($data['id']){
					$requestmodel = Request::find()->where(['id' => $data['id']])->one();
					$canedit = $this->canEditTc($requestmodel);
					if($canedit==0){
						return $responsedata;
					}
				}else{
					$canadd = $this->canAddTc($data['app_id']);
					if($canadd==0){
						return $responsedata;
					}
				}
				
				$standard_ids = $data['standard_id'];
				if(is_array($standard_ids) && count($standard_ids)>1)
				{
					$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();

					$StandardCombination = StandardCombination::find()->where(['status'=>0])->alias('t');
					$StandardCombination = $StandardCombination->join('inner join', 'tbl_standard_combination_standard as combination','t.id =combination.standard_combination_id');
					$StandardCombination = $StandardCombination->andWhere(['combination.standard_id'=>$standard_ids]);
					$StandardCombination = $StandardCombination->one();
					if($StandardCombination!==null){
						
						sort($standard_ids);

						$command = $connection->createCommand("select GROUP_CONCAT(combstd.standard_id order by combstd.standard_id asc ) as standardids from tbl_standard_combination as comb inner join tbl_standard_combination_standard as combstd on comb.id=combstd.standard_combination_id where combstd.standard_id in (".implode(',',$standard_ids).") GROUP BY comb.id HAVING standardids = '".implode(',',$standard_ids)."'");

						$result = $command->queryOne();
						if($result  === false)
						{
							return $responsedata=array('status'=>0,'message'=>["standard_id"=>["Standard Combination is not invalid"]]);
						}

					}
				}
				
				
				if(isset($data['id']) && $data['id']>0)
				{
					$model = Request::find()->where(['id' => $data['id']])->one();
					if($model===null)
					{
						$model = new Request();
						$editStatus=0;
						$model->created_by = $userData['userid'];

					}else{
						$existing_unitid = $model->unit_id;
						$existing_appid = $model->app_id;
						$model->updated_by = $userData['userid'];	
					}
				}else{
					$editStatus=0;
					$model = new Request();	
					$model->created_by = $userData['userid'];
				}	
				
				$model->app_id = $data['app_id'];	
				$model->unit_id = $data['unit_id'];	
				$model->buyer_id = $data['buyer_id'];
				$model->is_brand_consent = $data['sel_reduction'];
				$model->invoice_type = $data['sel_fasttrack'];

                if(isset($data['sel_fasttrack']) && $data['sel_fasttrack']!='' && $data['sel_fasttrack']==1 && $data['sel_fasttrack_addt']!='' )
				{
					$model->fasttrack_addtional_charges = $data['sel_fasttrack_addt'];
				}

				$model->facility_id = $data['facility_id'];	
				//TC TYPE
				if($data['tc_type'] == "scope"){
					$model->tc_type = 1;
				}else if($data['tc_type'] == "facility"){
					$model->tc_type = 2;
				}
					
				$model->sel_tc_type = $data['sel_tc_type'];
				$model->sel_lastpro_info = $data['sel_lastpro_info'];
				$model->standard_id = $data['standard_id'];	
				$inspection_body_id = '';
				$country_of_dispach = '';

				$inspection_bodyModel = InspectionBody::find()->where(['type'=>'inspection','status'=>0])->one();
				if($inspection_bodyModel!==null){
					$inspection_body_id = $inspection_bodyModel->id;
				}
				$ApplicationUnitModel = ApplicationUnit::find()->where(['id'=>$data['unit_id']])->one();
				if($ApplicationUnitModel!== null){
					$country_of_dispach = $ApplicationUnitModel->country_id;
				}
				$model->inspection_body_id = $inspection_body_id;
				$model->country_of_dispach = $country_of_dispach;					
				$model->usda_nop_compliant = $data['usda_nop_compliant'];	
				$model->comments = $data['comments'];	
										
				if($model->validate() && $model->save())
				{	

					if($data['sel_reduction']==1 && !$this->tcConsent($data,$model->id)){
						return $responsedata;
					}
					$manualID = $model->id;
					$existingstandard = [];
					$RequestStandard = RequestStandard::find()->where(['tc_request_id' => $manualID])->all();
					if(count($RequestStandard)>0){
						foreach($RequestStandard as $rstandard){
							$existingstandard[] = $rstandard->standard_id;
						}
					}
					$diffresult=array_diff($existingstandard,$data['standard_id']);

					if(count($diffresult)>0 || ($existing_unitid!='' && $existing_unitid!=$model->unit_id) || ($existing_appid!='' && $existing_appid!=$model->app_id) ){
						$TcRequestProductModel = RequestProduct::find()->where(['tc_request_id'=>$manualID])->all();
						if(count($TcRequestProductModel)>0)
						{
							foreach($TcRequestProductModel as $productModel){
								$this->deleteRequestProductData($productModel->id);
							}
						}
					}
					
					TcRequestIfoamStandard::deleteAll(['tc_request_id' => $manualID]);
					if(isset($data['ifoam_standard']) && is_array($data['ifoam_standard']) && count($data['ifoam_standard'])>0)
					{
						foreach ($data['ifoam_standard'] as $value)
						{ 
							$requeststd = new TcRequestIfoamStandard();
							$requeststd->tc_request_id = $manualID;
							$requeststd->ifoam_standard_id = $value;
							$requeststd->save();
						}
					}
					
					$RequestStdIds = [];
					RequestStandard::deleteAll(['tc_request_id' => $manualID]);
					if(is_array($data['standard_id']) && count($data['standard_id'])>0)
					{
						foreach ($data['standard_id'] as $value)
						{ 
							$requeststd = new RequestStandard();
							$requeststd->tc_request_id = $manualID;
							$requeststd->standard_id = $value;
							$requeststd->save();
							$RequestStdIds[] = $value;
						}
					}
					
					if($editStatus==0)
					{
						$usda_nop = ($model->usda_nop_compliant==1? "Yes" : "No" );

						$tc_std_code_array=array();
						if(count($model->standard)>0){
							foreach($model->standard as $reqstandard){
								$standardCode = $reqstandard->standard->code;
								$tc_std_code_array[]=$standardCode;
							}
						}
						
						$tc_std_code='';
						$additional_dec_tc_std_code='';
						$tc_std_code=implode(", ",$tc_std_code_array);
						$additional_dec_tc_std_code=implode(",",$tc_std_code_array);
						
						$standard_ids = $RequestStdIds;
						$standard_declaration_content = '';
						if(is_array($standard_ids) && count($standard_ids)>0)
						{
							sort($standard_ids);
							$command = $connection->createCommand("select comb.declaration_content as declaration_content,GROUP_CONCAT(combstd.standard_id order by combstd.standard_id asc ) as standardids  from tbl_standard_combination as comb inner join tbl_standard_combination_standard as combstd on comb.id=combstd.standard_combination_id where 1=1 GROUP BY comb.id HAVING standardids = '".implode(',',$standard_ids)."'");
							$result = $command->queryOne();
							if($result  !== false)
							{
								$standard_declaration_content = $result['declaration_content'];
							}
						}
												
						$TcDeclarationContent='';
							// foreach($standard_ids as $standardid) {
						
						// 	echo implode(',',$standard_ids);
						// 	$tcdeclarationmodel = TcRequestDeclaration::find()->where(['standard_id'=>implode(',',$standard_ids)])->one();
                        //     $TcDeclarationContent = $tcdeclarationmodel->declaration;
						// }
						$tcdeclarationmodel = TcRequestDeclaration::find()->where(['standard_id'=>implode(',',$standard_ids)])->one();
                        $TcDeclarationContent = $tcdeclarationmodel->declaration;
						$model->declaration=$TcDeclarationContent;

						//print_r($tc_std_code);
						// USDA NOP Rules Declaration Changes as per the user selection
						if($additional_dec_tc_std_code == "GOTS"){
							if($model->usda_nop_compliant == 1) {
								$model->additional_declaration='<b>Certification of the organic material used for the products listed complies with USDA NOP rules :<span>&#x2611</span>'.$usda_nop.' <span>&#9744</span> No </b> <br>
								(relevant information for products marketed and sold in the US; obligatory information for any  GOTS TC)';
							}else if($model->usda_nop_compliant == 2) {
								$model->additional_declaration='<b>Certification of the organic material used for the products listed complies with USDA NOP rules :<span>&#9744</span>Yes<span>&#x2611</span>'.$usda_nop.'</b> <br>
								(relevant information for products marketed and sold in the US; obligatory information for any  GOTS TC)';
							}
						} else if($tc_std_code =="OCS" ||  $tc_std_code =="GRS, OCS"  ||  $tc_std_code =="OCS, GRS" ||  $tc_std_code =="OCS, RCS" ||  $tc_std_code =="RCS, OCS" ) 
						{
							if($model->usda_nop_compliant == 1) {
								$model->additional_declaration='<b>Certification of the organic material used for the products listed complies with USDA NOP rules :<span>&#x2611</span>'.$usda_nop.' <span>&#9744</span> No </b> <br>
								(relevant information for products marketed and sold in the US; obligatory information for any  OCS TC)';
							}else if($model->usda_nop_compliant == 2) {
								$model->additional_declaration='<b>Certification of the organic material used for the products listed complies with USDA NOP rules :<span>&#9744</span>Yes<span>&#x2611</span>'.$usda_nop.'</b> <br>
								(relevant information for products marketed and sold in the US; obligatory information for any  OCS TC)';
							}
						}
						$model->standard_declaration = $standard_declaration_content;
						$model->save();
					}
					
					$applicationCompanyName='';
					$applicationCompanyAddress='';
					$applicationCompanyUnitName='';
					$applicationCompanyUnitAddress='';					
					$applicationModelObject = $model->application->currentaddress;
					if($data['tc_type'] == "scope")
					{
						$applicationCompanyName=$applicationModelObject->company_name;
						//$model->company_name=$applicationCompanyName;

					}
					else if($data['tc_type'] == "facility")
					{

						$Facility_Name = ApplicationUnit::find()->where(['id'=>$data['facility_id'],'app_id'=>$data['app_id']])->one();
						// $facilityModelObject = $model->application->facilityname;
						$applicationCompanyName=$Facility_Name->name;
						//$model->company_name=$applicationFacilityName;
					}
					
					$model->company_name=$applicationCompanyName;					
					//$applicationCompanyName=$applicationModelObject->company_name ;
					$applicationCompanyAddress=$applicationModelObject->address ;
										
					$applicationUnitModelObject = $model->applicationunit;
					if($applicationUnitModelObject->unit_type==1)
					{
						$applicationCompanyUnitName=$applicationModelObject->unit_name;
						$applicationCompanyUnitAddress=$applicationModelObject->unit_address;
					}else{
						$applicationCompanyUnitName=$model->applicationunit->name;
						$applicationCompanyUnitAddress=$model->applicationunit->address;
					}
					
										
					//$model->company_name=$applicationCompanyName;
					$model->unit_name=$applicationCompanyUnitName;

					$app_change_address_id=$applicationModelObject->id;
					$model->address_id=$app_change_address_id;
					$model->save();
									
					$userMessage = 'TC Application has been created successfully';
					if($editStatus==1)
					{
						$userMessage = 'TC Application has been updated successfully';
					}				
					$responsedata=array('status'=>1,'message'=>$userMessage,'id'=>$model->id);	
				}
			}
		}
		return $this->asJson($responsedata);
	}
    
	public function tcConsent($data,$id)
	{
		$re_status=false;
		$tcbrnmod = TcRequestBrandConsent::find()->where(['app_id'=>$data['app_id'],'unit_id'=>$data['unit_id'],'tc_request_id'=>$id])->one();
		if($tcbrnmod!=='' && $tcbrnmod!==null){
			$tcbrnmod->brand_id=$data['brand_id'];
			$tcbrnmod->authorized_person_name=$data['authorized_name'];
			$tcbrnmod->brand_consent_date=$data['brand_consent_date']?date('Y-m-d',strtotime($data['brand_consent_date'])):'';
		
			if($tcbrnmod->save()){
				$re_status=true;
			}
		}else{
			
			$tcbrnmodel = new TcRequestBrandConsent(); 
			$tcbrnmodel->app_id=$data['app_id'];
			$tcbrnmodel->unit_id=$data['unit_id'];
			$tcbrnmodel->tc_request_id=$id;
			$tcbrnmodel->brand_id=$data['brand_id'];
			$tcbrnmodel->authorized_person_name=$data['authorized_name'];
			$tcbrnmodel->brand_consent_date=$data['brand_consent_date']?date('Y-m-d',strtotime($data['brand_consent_date'])):'';
			
			if($tcbrnmodel->validate() && $tcbrnmodel->save()){
				
				$re_status=true;
			}
		}
	
		return $re_status;
	}
	public function actionAddDeclaration()
	{
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$data = Yii::$app->request->post();
		if ($data) 
		{	
			if(isset($data['id']) && $data['id']>0)
			{
				$userrole = Yii::$app->userrole;
				$userid=$userrole->user_id;				
				$user_type=$userrole->user_type;
				$role=$userrole->role;
				$rules=$userrole->rules;
				$franchiseid=$userrole->franchiseid;		
				$resource_access=$userrole->resource_access;
				$is_headquarters =$userrole->is_headquarters;
				$role_chkid=$userrole->role_chkid;
				
				$modelRequest=new Request();
				
				$arrStatusforDec=array($modelRequest->arrEnumStatus['waiting_for_osp_review'],$modelRequest->arrEnumStatus['pending_with_osp'],$modelRequest->arrEnumStatus['waiting_for_review'],$modelRequest->arrEnumStatus['review_in_process']);
				$model = Request::find()->alias('t')->where(['t.id' => $data['id'],'t.status'=>$arrStatusforDec]);
				//$model = $model->innerJoinWith(['application as app','reviewer as reviewer']);					
				$model = $model->one();
				if($model!==null)
				{	
					if(!Yii::$app->userrole->isValidApplication($model->app_id))
					{
						$responsedata=array('status'=>0,'message'=>'Application is not valid.');
						return $this->asJson($responsedata);
					}
					
					if($resource_access != '1' && $resource_access != '2')
					{
						$tcStatus = $model->status;
						if($user_type==Yii::$app->params['user_type']['user'] && $is_headquarters!=1 )
						{
							if($model->application->franchise_id!=$franchiseid)
							{
								return false;
							}								
						}
						
						if($user_type== Yii::$app->params['user_type']['user'] && in_array('assign_as_oss_review_for_tc',$rules)){
							$arrStatusforDec=array($modelRequest->arrEnumStatus['waiting_for_osp_review'],$modelRequest->arrEnumStatus['pending_with_osp']);
							if(!in_array($tcStatus,$arrStatusforDec))
							{
								return false;
							}
						}elseif($user_type== Yii::$app->params['user_type']['user'] && in_array('application_review',$rules)){
							$arrStatusforDec=array($modelRequest->arrEnumStatus['waiting_for_review'],$modelRequest->arrEnumStatus['review_in_process']);
							if($model->reviewer->user_id!=$userid || !in_array($tcStatus,$arrStatusforDec))
							{
								return false;
							}							
						}else if($user_type== Yii::$app->params['user_type']['franchise']  && $is_headquarters!=1){
							if($resource_access == '5'){
								$userid = $franchiseid;
							}
							
							$arrStatusforDec=array($modelRequest->arrEnumStatus['waiting_for_osp_review'],$modelRequest->arrEnumStatus['pending_with_osp']);
							if($model->application->franchise_id!=$userid || !in_array($tcStatus,$arrStatusforDec))
							{
								return false;
							}						
						}					
					}
					if($model->status == $modelRequest->arrEnumStatus['waiting_for_osp_review'] || $model->status == $modelRequest->arrEnumStatus['pending_with_osp']){
						$model->oss_declaration_update_status = 1;
					}else if($model->status == $modelRequest->arrEnumStatus['review_in_process']){
						$model->reviewer_declaration_update_status = 1;
					}

					$model->declaration = $data['declaration'];
					$model->additional_declaration = $data['additional_declaration'];
					$model->standard_declaration = $data['standard_declaration'];
					TcRequestIfoamStandard::deleteAll(['tc_request_id' => $data['id']]);
					if(isset($data['ifoam_standard']) && is_array($data['ifoam_standard']) && count($data['ifoam_standard'])>0)
					{
						foreach ($data['ifoam_standard'] as $value)
						{ 
							$requeststd = new TcRequestIfoamStandard();
							$requeststd->tc_request_id =  $data['id'];
							$requeststd->ifoam_standard_id = $value;
							$requeststd->save();
						}
					}
					// Update The IFOAM Standard					
					if($model->save())
					{
						$responsedata=array('status'=>1,'message'=>'Declaration has been updated successfully');
					}
				}
			}
		}
		return $this->asJson($responsedata);
	}
	
	public function actionView()
    {
		$datapost = Yii::$app->request->post();
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		$modelObj = new Request();
		$ospreviewmodal = new RequestFranchiseComment();
		$reviewerreviewmodal = new RequestReviewerComment();
        $model = $this->findModel($datapost['id']);
        if ($model !== null)
		{
			$data = array();
			$data["id"]=$model->id;
			if($model->tc_type == 1){
				$data["app_id"]=$model->app_id;
			}
			else if($model->tc_type == 2){
				$data["app_id"]=$model->facility_id;
			}
			$data['actual_app_id']=$model->app_id;			
			$data["unit_id"]=$model->unit_id;
			$data["facility_id"]=$model->facility_id;
			$data["tc_type"]=$model->tc_type;
			$data['unit_type']=$model->applicationunit->unit_type;
			$data['sel_reduction']=$model->is_brand_consent;
			$data['sel_tc_type']=$model->sel_tc_type;
			$data['sel_lastpro_info']=$model->sel_lastpro_info;
			$data['qua_exam_file']=$model->tc_brand_consent_file;
			$data["request_status"]=$model->status;
			$data["request_status_label"]=$model->arrStatus[$model->status];
			$data['sel_finacial_evidence']=$model->finacial_evidence_consent;
			$data['finacial_doc_reason']=$model->finacial_doc_reason;

			//$data["tc_number_temp"]=$model->tc_number_temp;
			$data["tc_number_cds"]=$model->tc_number_cds;
			$data['tc_number']=$model->arrEnumStatus['approved']==$model->status?$model->tc_number:'TEMP'.$model->tc_number;
	
			// $brandIds =[];
			// if(count($model->tcbrandconsent)>0){
			// 	foreach($model->tcbrandconsent as $cons){
			// 		$brandIds[]=$cons->brand_id;
			// 	}
			// }

			$data['brand_id']=isset($model->tcbrandconsent->brand_id)?$model->tcbrandconsent->brand_id:'';
			$data['authorized_name']=isset($model->tcbrandconsent->authorized_person_name)?$model->tcbrandconsent->authorized_person_name:'';
			$data['brand_consent_date']=isset($model->tcbrandconsent->brand_consent_date)?$model->tcbrandconsent->brand_consent_date:'';

			$applicationCompanyName='';
			$applicationCompanyAddress='';
			$applicationCompanyUnitName='';
			$applicationCompanyUnitAddress='';
			
			$applicationModelObject = $model->applicationaddress;
			$applicationCompanyName=$applicationModelObject->company_name ;
			$applicationCompanyAddress=$applicationModelObject->address.', '.$applicationModelObject->city.' - '.$applicationModelObject->zipcode;
			
			$applicationUnitModelObject = $model->applicationunit;
			if($applicationUnitModelObject->unit_type==1)
			{
				$applicationCompanyUnitName=$applicationModelObject->unit_name;
				$applicationCompanyUnitAddress=$applicationModelObject->unit_address.', '.$applicationModelObject->city.' - '.$applicationModelObject->zipcode;
			}else{
				$applicationCompanyUnitName=$model->applicationunit->name;
				$applicationCompanyUnitAddress=$model->applicationunit->address.', '.$model->applicationunit->city.' - '.$model->applicationunit->zipcode;
			}	
						
			if($model->tc_type == 1){
				$data["app_id_label"]=$applicationCompanyName;
				$data["app_address"]=$applicationCompanyAddress;
			}
			else if($model->tc_type == 2){
				$facilityModelObject = $model->facilityaddress;
				$data["app_id_label"]=$facilityModelObject->name ;
				$data["app_address"]=$facilityModelObject->address.', '.$facilityModelObject->city.' - '.$facilityModelObject->zipcode;
			}			
			$data["unit_id_label"]=$applicationCompanyUnitName;
			$data["unit_address"]=$applicationCompanyUnitAddress;		
			
			$data["buyer_id"]=$model->buyer_id;
			$data["buyer_id_label"]=$model->buyer->name;
			$data["buyer_address"]=$model->buyer->address;
			$data["buyer_license_number"]=$model->buyer->client_number;


			$data["wcomment"]=$model->wcomment;

			$data["declaration"]=$model->declaration;
			$data["additional_declaration"]=$model->additional_declaration;
			$data["standard_declaration"]=$model->standard_declaration;
			
			$data["overall_input_status"]=$model->overall_input_status;

			$data["oss_declaration_update_status"]=$model->oss_declaration_update_status;
			$data["reviewer_declaration_update_status"]=$model->reviewer_declaration_update_status;
			/*
			$data["seller_id"]=$model->seller_id;
			$data["seller_id_label"]=$model->seller->name;
			$data["seller_address"]=$model->seller->address;
			$data["seller_license_number"]=$model->seller->client_number;
			*/

			//$data["transport_id"]=$model->transport_id;
			//$data["transport_id_label"]=$model->transport->name;
			
			//$data["consignee_id"]=$model->consignee_id;
			//$data["consignee_id_label"]=$model->consignee->name;
			//$data["consignee_address"]=$model->consignee->address;
			//$data["consignee_license_number"]=$model->consignee->client_number;

			//$data["standard_id"]=$model->standard_id;
			//$data["standard_id_label"]=implode(', ',$model->applicationstandard->standard->name);
			//$data["purchase_order_number"]=$model->purchase_order_number;
			//$data["apeda_npop_compliant_label"]=($model->apeda_npop_compliant==1)?"Yes":"No";	
			//$data["apeda_npop_compliant"]=$model->apeda_npop_compliant;
			$data["comments"]=$model->comments;
			$data["visible_to_brand"]=$model->visible_to_brand;
			$data["usda_nop_compliant"]=$model->usda_nop_compliant;
			
			$data["visible_to_brand_label"]=($model->visible_to_brand==1)?"Yes":"No";
			$data["usda_nop_compliant_label"]=($model->usda_nop_compliant==1)?"Yes":"No";
			
			//$data["shipment_number"]=$model->shipment_number;

			$data["country_of_dispach"]=$model->country_of_dispach;
			//$data["country_of_destination"]=$model->country_of_destination;

			$data["country_of_dispach_label"]=$model->dispatchcountry?$model->dispatchcountry->name:'';
			//$data["country_of_destination_label"]=$model->destinationcountry?$model->destinationcountry->name:'';
			
			//$data["bl_copy"]=$model->bl_copy;
			
			$data["inspection_body_id"]=$model->inspection_body_id;
			$data["inspection_body_id_label"]=$model->inspectionbody?$model->inspectionbody->name:'';
			$data["inspection_body_address"]=$model->inspectionbody?$model->inspectionbody->description:'';

			//$data["certification_body_address"]=$model->certificationbody->description;
			//$data["certification_body_id"]=$model->certification_body_id;
			//$data["certification_body_id_label"]=$model->certificationbody->name;
			$data['reviewer_id'] =$model->reviewer?$model->reviewer->user_id:'';

			$standardIds = [];
			$standardLabels = [];
			if(count($model->standard)>0){
				foreach($model->standard as $reqstandard){
					$standardIds[] =  $reqstandard->standard_id;
					$standardLabels[] =  $reqstandard->standard->name;
					$standardCodeLabels[] =  $reqstandard->standard->code;

					$standardCodeLabelsCheck[] =  strtolower($reqstandard->standard->code);
				}
			}
			
			$data["standard_id"]=$standardIds;	
			$data["standard_id_label"]=implode(', ',$standardLabels);
 			//$data["standard_id_code_label"]=implode(', ',$standardCodeLabels);
			$data["standard_id_code_label"]=$standardCodeLabels;	

			$data["show_additional_declaration"]=0;
			if(in_array('gots',$standardCodeLabelsCheck) || in_array('ocs',$standardCodeLabelsCheck)|| in_array('rcs',$standardCodeLabelsCheck))
			{
				$data["show_additional_declaration"]=1;
			}
			
			$ifoamstandardIds = [];
			$ifoamstandardLabels = [];
			if(count($model->ifoamstandard)>0){
				foreach($model->ifoamstandard as $reqstandard){
					$ifoamstandardIds[] =  "".$reqstandard->ifoam_standard_id;
					$ifoamstandardLabels[] =  $reqstandard->ifoamStd?$reqstandard->ifoamStd->name:'';
				}
			}

			$data["ifoam_standard"]=$ifoamstandardIds;	
			$data["ifoam_standard_id_label"]=implode(",<br>",$ifoamstandardLabels);
			$data["ifoam_standard_id_label_list"]=$ifoamstandardLabels;
			//$data['tc_submitted_date']=date($date_format,$model->application->created_at);
			$data['tc_submitted_date']=$model->submit_to_oss_at?date($date_format,strtotime($model->submit_to_oss_at)):'-';
			
			//$data['purchase_order_number']=$model->purchase_order_number;	
			$data['grand_total_net_weight']=$model->grand_total_net_weight;	
			$data['grand_total_used_weight']=$model->grand_total_used_weight;	
			
			$data['created_at']=date($date_format,$model->created_at);
			$data['created_by_label']= $model->username?$model->username->first_name.' '.$model->username->last_name:'';
			$data['sel_fasttrack'] = $model->invoice_type;
            $data['sel_fasttrack_addt'] = $model->fasttrack_addtional_charges;			

			$customeroffernumber = $model->application->customer->customer_number;
			$TransactionCertificateNo='';					
			$draftText='';
			if($model->status!=$model->arrEnumStatus['approved'])
			{
				$draftText='DRAFT ';
				$TransactionCertificateNo=$model->id;
			}else{
				$TransactionCertificateNo=$model->tc_number;
			}					
			$tcFileName = 'TRANSACTION_CERTIFICATE_'.$customeroffernumber.'_'.$TransactionCertificateNo.'.pdf';
			$data['tc_filename'] = $tcFileName;
			
			$showedit= $this->canEditTc($model);
			$data["showedit"] = $showedit;
			
			$customeroffernumber = $model->application->customer->customer_number;
			$TransactionCertificateNo='';					
			$draftText='';
			if($model->status!=$model->arrEnumStatus['approved'])
			{
				$draftText='DRAFT ';
				$TransactionCertificateNo=$model->id;
			}else{
				$TransactionCertificateNo=$model->tc_number;
			}					
			$tcFileName = 'TRANSACTION_CERTIFICATE_'.$customeroffernumber.'_'.$TransactionCertificateNo.'.pdf';
			$data['tc_filename'] = $tcFileName;
			
			$reqdata = [];
			//$reqdata = $data;
			$reqdata['requestdata'] = $data;
			
			$reqdata['requestproduct'] = $this->gettcproduct($model->id);


			$evidencedetails = [];
			if(count($model->evidence)>0){
				foreach($model->evidence as $reqevidence){
					 
						//sales_invoice_with_packing_list
					$evidence_type = $reqevidence->evidence_type;
					if($evidence_type=='sales_invoice'){
						$evidence_type = 'sales_invoice_with_packing_list';
					}else if($evidence_type=='mass_balance'){
						$evidence_type = 'mass_balance_sheet';
					}
					$evidencedetails[$evidence_type][] =  [
						'id' => $reqevidence->id,
						'name'=>$reqevidence->evidence_file,
						'sel_product_evidence'=>$reqevidence->sel_product_evidence
						];
					 
				}
			}
			$reqdata['requestevidence'] = $evidencedetails;

			$reviewarr = [];
			$ospmodal = $model->franchisecmt;
			if(count($ospmodal)>0)
			{
				$ospcmts = [];
				foreach($ospmodal as $res)
				{
					$cmt = [];
					$cmt['status'] = $res->status;
					$cmt['status_label'] = $ospreviewmodal->arrStatusLabel[$res->status];
					$cmt['comment'] = $res->comment;
					$cmt['created_at'] = date($date_format,$res->created_at);
					$cmt['created_by'] = $res->createdbydata->first_name." ".$res->createdbydata->last_name;
					$ospcmts[] = $cmt;
				}
				$reviewarr['osp_reviews'] = $ospcmts;
			}


			$reviewermodal = $model->reviewercmt;
			if(count($reviewermodal)>0)
			{
				$reviewercmts = [];
				foreach($reviewermodal as $res)
				{
					$cmt = [];
					$cmt['status'] = $res->status;
					$cmt['status_label'] = $reviewerreviewmodal->arrStatusLabel[$res->status];
					$cmt['comment'] = $res->comment;
					$cmt['created_at'] = date($date_format,$res->created_at);
					$cmt['created_by'] = $res->createdbydata->first_name." ".$res->createdbydata->last_name;
					$reviewercmts[] = $cmt;
				}
				$reviewarr['reviewer_reviews'] = $reviewercmts;
			}

			$requestreviewermodal = $model->reviewer;
			if($requestreviewermodal!==null)
			{
				$reviewerdata = [];
				$reviewerdata['reviewer'] = $requestreviewermodal->user->first_name." ".$requestreviewermodal->user->last_name;
				$reviewerdata['assigned_date'] = date($date_format,$requestreviewermodal->created_at);
				$reviewarr['reviewer'] = $reviewerdata;
			}
			$modelIfoamStandard = TcIfoamStandard::find()->select('id,name');
			$modelIfoamStandard = $modelIfoamStandard->asArray()->all();
			
			

			$pdtdata= [];
			$pdtdata['unit_id'] = $model->unit_id;//236;//$model->unit_id;
			$pdtdata['standard_id'] = $standardIds;//[2,3];//$standardIds;//$model->standard_id;
			$productlist = $this->getapplicationproduct($pdtdata);

			$reqdata['productlist'] = $productlist;
			$reqdata['ifoamstandard'] = $modelIfoamStandard;

			$reqdata['enumstatus'] = $modelObj->arrEnumStatus;
			
			//if( $data->)	
            return ['data'=>$reqdata,'reviewdetails'=>$reviewarr];
        }

	}

	public function actionGetproductdata()
    {
		$data = Yii::$app->request->post();
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');

        //if($data)
        if($data)
		{
			//$unit_id=$data['unit_id'];
			$tc_request_id=$data['tc_request_id'];
			$pdtdata= [];
			$requestproduct  = $this->gettcproduct($tc_request_id);
			$responsedata=array('status'=>1,'data'=>$requestproduct);
			

        }
        return $responsedata;
	}
	
	public function getapplicationproduct($data){

		$unit_id=$data['unit_id'];
		$standard_id=$data['standard_id'];
			//$unit_id=236;
			//$standard_id=2;
		
		$connection = Yii::$app->getDb();
		
		$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();
		$connection->createCommand("SET SESSION group_concat_max_len = 1000000;")->execute();
		$command = $connection->createCommand("SELECT group_concat(pdtstd.id) as productstandard FROM `tbl_application_unit_product` as unitpdt inner join tbl_application_product_standard as pdtstd on unitpdt.`application_product_standard_id`= pdtstd.id and unitpdt.`unit_id`='".$unit_id."' and pdtstd.standard_id in (".implode(',',$standard_id).") ");
		$result = $command->queryOne();
		$appprdarr_details = [];
		if($result  !== false)
		{
			//$appProduct= $appProduct->innerJoinWith(['productstandard as productstandard']);
			//$appProduct = $appProduct->andWhere(['productstandard.id'=> $result['productstandard'] ])->all();
			 
			$wastagepdtlist = [];
			$productstandards = explode(',',$result['productstandard']);
			$productstandardObj = ApplicationProductStandard::find()->where(['id' => $productstandards])->all();
			if(is_array($productstandardObj) && count($productstandardObj)>0)
			{
				$i=0;
				foreach($productstandardObj as $productstandard){
					$productMaterialList = [];

					$prd=ApplicationProduct::find()->where(['t.id' =>$productstandard->application_product_id  ])->alias('t')->one();
					$materialcompositionname = '';
					if(!empty($prd->productmaterial) && is_array($prd->productmaterial) && count($prd->productmaterial)>0){
						foreach($prd->productmaterial as $productmaterial){
							$productMaterialList[]=[
								'app_product_id'=>$productmaterial->app_product_id,
								'material_id'=>$productmaterial->material_id,
								'material_name'=>$productmaterial->material_name,//$productmaterial->material->name,
								'material_type_id'=>$productmaterial->material_type_id,
								'material_type_name'=> $productmaterial->material_type_name,//$productmaterial->material->material_type[$productmaterial->material_type_id],
								'material_percentage'=>$productmaterial->percentage
							];
							$materialcompositionname = $materialcompositionname.$productmaterial->percentage.'% '.$productmaterial->material_name.' + ';

						}
						$materialcompositionname = rtrim($materialcompositionname," + ");
					}
                    if(!empty($prd->productmaterial)) {

                    
					$arrsForPdtDetails=array(
						'id'=>$prd->product_id,
						'autoid'=>$prd->id,
						'addition_type'=>$prd->product_addition_type,
						'name'=>$prd->product_name, //($prd->product?$prd->product->name:''),
						'wastage'=>$prd->wastage,
						'product_type_name' => $prd->product_type_name, //isset($prd->producttype)?$prd->producttype->name:'',
						'product_type_id'=>isset($prd->producttype)?$prd->producttype->id:'',
						'productMaterialList' => $productMaterialList,
						'materialcompositionname' => $materialcompositionname,
					);

					$unit_product_id= 0;
					$unit_product= ApplicationUnitProduct::find()->where(['unit_id'=>$unit_id,'application_product_standard_id'=>$productstandard->id])->one();
					if($unit_product!==null){
						$unit_product_id = $unit_product->id;
					}
					$arrsForPdtDetails['unit_product_id'] = $unit_product_id;
					$arrsForPdtDetails['product_standard_id'] = $productstandard->id;
					//$arrsForPdtDetails['pdt_index'] = $pdt_index;
					$arrsForPdtDetails['standard_id'] = $productstandard->standard_id;
					$arrsForPdtDetails['standard_name'] = $productstandard->standard->name;
					$arrsForPdtDetails['label_grade'] = $productstandard->label_grade_id;
					$arrsForPdtDetails['label_grade_name'] = $productstandard->label_grade_name;//$productstandard->labelgrade->name;
					//$arrsForPdtDetails['addition_type'] = $productstandard->addition_type;
					$arrsForPdtDetails['pdtListIndex'] = $i;
					

					//$appprdarr_details[]= $arrsForPdtDetails;
					$appprdarr_details[$unit_product_id]= $arrsForPdtDetails['name'].' | '.$arrsForPdtDetails['product_type_name'].' | '.$arrsForPdtDetails['wastage'].'% wastage | '.$arrsForPdtDetails['materialcompositionname'].' | '.$arrsForPdtDetails['standard_name'].' | '.$arrsForPdtDetails['label_grade_name'];
					$wastagepdtlist[$unit_product_id] = $prd->wastage;
					$i++;
                    }
					//$pdt_index++;
				}
			}			 
		}
		return $appprdarr_details;
	}

	public function actionAddproductdata()
	{
		$data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($data) 
		{
			$userData = Yii::$app->userdata->getData();
			
			$modelRequestProduct = new RequestProduct();
			
			$TotalGrossWeight=0;
			$TotalNetWeight=0;
			$TotalCertifiedWeight=0;
			$TotalWastageWeight=0;
			$GrandTotalNetWeight=0;
			
			if(isset($data['tc_request_id']) && $data['tc_request_id']>0)
			{
				$RequestModel = Request::find()->where(['id' => $data['tc_request_id']])->one();				
				if($RequestModel!==null)
				{
					if(!Yii::$app->userrole->isValidApplication($RequestModel->app_id))
					{
						$responsedata=array('status'=>0,'message'=>'Application is not valid.');
						return $responsedata;
					}
					
					$showedit= $this->canEditTc($RequestModel);
					if($showedit==0){
						return $responsedata;
					}
					
					$TotalGrossWeight=$RequestModel->total_gross_weight;
					$TotalNetWeight=$RequestModel->total_net_weight;;
					$TotalCertifiedWeight=$RequestModel->total_certified_weight;
					$TotalWastageWeight=$RequestModel->total_wastage_weight;
					$GrandTotalNetWeight=$RequestModel->grand_total_net_weight;
										
					$editStatus = 0;
					if(isset($data['id']) && $data['id']>0)
					{
						$model = RequestProduct::find()->where(['id' => $data['id']])->one();
						if($model===null)
						{
							$model = new RequestProduct();
							$editStatus=0;
							$model->status = $modelRequestProduct->arrEnumStatus['open'];
						}else{
							
							$TotalGrossWeight=$TotalGrossWeight-$model->gross_weight;				
							$TotalNetWeight=$TotalNetWeight-$model->net_weight;
							$TotalCertifiedWeight=$TotalCertifiedWeight-$model->certified_weight;
							$TotalWastageWeight=$TotalWastageWeight-$model->wastage_weight;
							$GrandTotalNetWeight=$GrandTotalNetWeight-$model->total_net_weight;			
							$editStatus=1;					
						}						
					}else{
						$editStatus=0;
						$model = new RequestProduct();
						$model->status = $modelRequestProduct->arrEnumStatus['open'];
					}	
				
					//$standarlength = $data['standardlength'];
					
					$check_product_is_array = is_array($data['product_id']);
					

					if($check_product_is_array == 1){
						$no_of_products = count($data['product_id']);
					} else {
						$no_of_products = 1;
					}

					$wastage=0;

					// wastage 
					$Unitproduct = ApplicationUnitProduct::find()->where(['id'=>$data['product_id']])->one();
						if($Unitproduct!==null){
							$productstd = $Unitproduct->product;
							if($productstd!==null){
								$wastage = $productstd->appproduct->wastage;
							}	
						}

					// Single Standard Tc standard id insertion 
					if($no_of_products == 1 || $no_of_products <= 1){
						$Unitproduct = ApplicationUnitProduct::find()->where(['id'=>$data['product_id']])->one();
						if($Unitproduct!==null){
							$productstd = $Unitproduct->product;
							$model->standard_id = $productstd->standard_id;	
						}
						if($check_product_is_array == 1){
							$MultipleStandardProducts = [];
							$MultipleStandardProducts = $data['product_id'];
						foreach($MultipleStandardProducts as $multiproduct){
						    $model->product_id = $multiproduct;	
							// Clearing Previous Record
							if($model->multiple_tc_id !==null){
						    $updating_multiple_records = RequestProductMultiple::find()->where(['multiple_tc_id'=>$model->multiple_tc_id])->all();
							if($updating_multiple_records!==null){
							foreach($updating_multiple_records as $del){
							$del->delete();
							}
							}
							}
							
							$model->multiple_tc_id = null;
						}
						} else {
							$model->product_id = $data['product_id'];
							$model->multiple_tc_id = null;
						}
					}

					// Multiple Tc code start here
					else if ($no_of_products > 1){
						$model->standard_id = null;	
						$model->product_id = null;

						// Clearing Previous Record 
						// $updating_multiple_records = RequestProductMultiple::find()->where(['multiple_tc_id'=>$model->multiple_tc_id])->all();
						// if($updating_multiple_records!==null){
						// 	foreach($updating_multiple_records as $del){
						// 	$del->delete();
						// 	}
						// }
						// Generating The Multiple Tc ID 
						$multi_tc_value_inc = RequestProduct::find()->select('multiple_tc_id')->where(['not', ['multiple_tc_id' => null]])->orderBy(['id'=>SORT_DESC])->one();
						$temp_multiple_tc = isset($multi_tc_value_inc->multiple_tc_id)?$multi_tc_value_inc->multiple_tc_id:null;
						
					if($editStatus == 0){
						if($temp_multiple_tc == null){
							$temp_multiple_tc = $temp_multiple_tc = 1 ;
						}else {
							$temp_multiple_tc =  $temp_multiple_tc+1 ;
						}
						$model-> multiple_tc_id = $temp_multiple_tc;
						
						$MultipleStandardProducts = [];
						$MultipleStandardProducts = $data['product_id'];
						foreach($MultipleStandardProducts as $multiproduct){
						$reqprodcutmulti = new RequestProductMultiple();
						// Multiple Tc Standards 
						$Unitproduct = ApplicationUnitProduct::find()->where(['id'=>$multiproduct])->one();
						if($Unitproduct!==null){
							$productstd = $Unitproduct->product;
							$reqprodcutmulti->standard_id = $productstd->standard_id;	
						}
						$reqprodcutmulti->multiple_tc_id= $temp_multiple_tc;
						$reqprodcutmulti->tc_request_id= $data['tc_request_id'];
						$reqprodcutmulti->product_id = $multiproduct;
						$reqprodcutmulti->save();
						}
					}					
					else if($editStatus == 1) {

						$reqprodcutmulti = RequestProductMultiple::find()->select('id')->where(['multiple_tc_id'=>$model->multiple_tc_id])->all();
						foreach($reqprodcutmulti as $key=>$updatedata){					
						$updateproduct = RequestProductMultiple::find()->where(['id'=>$updatedata->id])->one();
						$productsid = $data['product_id'][$key];
						$Unitproduct = ApplicationUnitProduct::find()->where(['id'=>$productsid])->one();

						if($Unitproduct!==null){
							$productstd = $Unitproduct->product;
							$updateproduct->standard_id = $productstd->standard_id;	
						}
						$updateproduct->multiple_tc_id= $model->multiple_tc_id;
						$updateproduct->tc_request_id= $data['tc_request_id'];
						$updateproduct->product_id = $productsid;
						$updateproduct->save();
						}					
					}

					}

					$model->tc_request_id = $data['tc_request_id'];	
				
					$model->trade_name = $data['trade_name'];	
					$model->packed_in = $data['packed_in'];
					$model->lot_ref_number = $data['lot_ref_number'];	
					$model->consignee_id = $data['consignee_id'];	
						
					$model->gross_weight = $data['gross_weight'];
					$model->net_weight = $data['net_weight'];
					// Add 0 when adding the product in the TC Application	
					$model->certified_weight = 0;

					// Adding Standard Certified Weight 
       				//$model->std_1_certified_weight = $data['std_1_certified_weight'];	
					//$model->std_2_certified_weight = $data['std_2_certified_weight'];

					// Supplimentry weigh
					$model->supplementary_weight = $data['supplementary_weight'];

					//if($model===null || $editStatus==0)
					//{			
					$model->wastage_percentage = $wastage;
					$model->product_wastage_percentage = $wastage;
					
					//if( $wastage>0){						
					//	$model->wastage_weight = (($data['net_weight']/(100-$wastage))*100)-$data['net_weight'];
					//}else{
					//	$model->wastage_weight = 0;	
					//}
					//}
					$model->wastage_weight = 0;

					$model->unit_information = $data['unit_information'];
					$model->purchase_order_no = $data['purchase_order_no'];
					$model->purchase_order_date = date("Y-m-d",strtotime($data['purchase_order_date']));
					$model->invoice_no = $data['invoice_no'];
					$model->invoice_date = date("Y-m-d",strtotime($data['invoice_date']));
					$model->transport_document_no = $data['transport_document_no'];
					$model->transport_company_name = $data['transport_company_name'];
					$model->vehicle_container_no = $data['vehicle_container_no'];

					$model->transport_document_date = date("Y-m-d",strtotime($data['transport_document_date']));
					//$model->production_date = date("Y-m-d",strtotime($data['production_date']));
					
					if($data['production_date'] == null ||  $data['production_date'] == '' || $data['production_date'] == "undefined aN, NaN" ){
						$model->production_date =  null;
					}else if($data['production_date'] != null) {
						$model->production_date = date("Y-m-d",strtotime($data['production_date']));
					}
					$model->transport_id = $data['transport_id'];					
					
					$model->additional_weight = 0;
					//$model->total_net_weight = $model->wastage_weight + $data['net_weight'] - $model->additional_weight;
					//$model->wastage_weight = ($data['certified_weight']*$data['wastage_percentage'])/100;

					$model->total_net_weight = $data['net_weight'] - $model->supplementary_weight;

					if($model->total_used_weight>=$model->total_net_weight)
					{
						$model->status = $modelRequestProduct->arrEnumStatus['input_added'];
					}else{
						$model->status = $modelRequestProduct->arrEnumStatus['open'];
					}									
						
					$model->created_by = $userData['userid'];			
					if($model->validate() && $model->save())
					{	
						$TotalGrossWeight=$TotalGrossWeight+$model->gross_weight;				
						$TotalNetWeight=$TotalNetWeight+$model->net_weight;
						//$TotalCertifiedWeight=$TotalCertifiedWeight+$model->certified_weight;
						$TotalWastageWeight=$TotalWastageWeight+$model->wastage_weight;
						$GrandTotalNetWeight=$GrandTotalNetWeight+$model->total_net_weight;

						$requestObj = $model->request;
						if($requestObj!==null)
						{
							$requestObj->total_gross_weight=$TotalGrossWeight;
							$requestObj->total_net_weight=$TotalNetWeight;
							//$requestObj->total_certified_weight=$TotalCertifiedWeight;
							$requestObj->total_wastage_weight=$TotalWastageWeight;
							$requestObj->grand_total_net_weight=$GrandTotalNetWeight;
							$requestObj->save();	
						}					

						$this->updateProductWeightToRequest($model->tc_request_id);
							
						$userMessage = 'Product has been created successfully';
						if($editStatus==1)
						{
							$userMessage = 'Product has been updated successfully';
						}	
						///$productlist = $this->gettcproduct($data['tc_request_id']);		
						//,'productlist'=>$productlist	
						$responsedata=array('status'=>1,'message'=>$userMessage);	
					}
				}	
			}		
		}
		return $responsedata;
	}

	public function gettcproduct($tc_request_id){

		$RequestProduct = RequestProduct::find()->where(['tc_request_id'=>$tc_request_id])->all();
		$productdata = [];
		$date_format = Yii::$app->globalfuns->getSettings('date_format');


		if(count($RequestProduct)>0){
			foreach ($RequestProduct as $pdtdata) {

				$productname = '';
				$productid='';
				$completepdtname = '';


				if ($pdtdata->multiple_tc_id == null){
				
					$Unitproduct = $pdtdata->unitproduct;

					//
					$multiple_product_id = array();
					$multiple_product_id[]=$pdtdata->product_id;
					$productid=$multiple_product_id;

					if($Unitproduct!== null)
					{
						$productstd = $Unitproduct->product;
						if($productstd!==null)				
					{
						$standard_name = $productstd->standard->name;
						$labelgradename = $productstd->label_grade_name;
						$productname = $productstd->appproduct->product_name;
						$producttypename = $productstd->appproduct->product_type_name;
						$wastage = $productstd->appproduct->wastage;
						$materialcompositionname = '';
						if(count($productstd->productmaterial) >0){
							foreach($productstd->productmaterial as $productmaterial){
								$productMaterialList[]=[
									'app_product_id'=>$productmaterial->app_product_id,
									'material_id'=>$productmaterial->material_id,
									'material_name'=>$productmaterial->material_name,
									'material_type_id'=>$productmaterial->material_type_id,
									'material_type_name'=> $productmaterial->material_type_name,//material->material_type[$productmaterial->material_type_id],
									'material_percentage'=>$productmaterial->percentage
								];
								$materialcompositionname = $materialcompositionname.$productmaterial->percentage.'% '.$productmaterial->material_name.' + ';
							}
							$materialcompositionname = rtrim($materialcompositionname," + ");
						}
						$completepdtname = $productname.' / '.$producttypename.' - '.$materialcompositionname.' <br>(Label Grade: '.$labelgradename.')';
					}
				}
				}else {
					$multi_tc_product_ref = RequestProductMultiple::find()->where(['multiple_tc_id'=>$pdtdata->multiple_tc_id])->all();
											
					$combined_product_name = array();
					$multiple_product_id = array();
				   foreach($multi_tc_product_ref as $multiref)
				   {

						  $multiple_product_id[]=$multiref->product_id;
						  //$productid=implode(",",$multiple_product_id);
						  $productid=$multiple_product_id;	
						// To get application unit Product
						   $Unitproduct = ApplicationUnitProduct::find()->where(['id'=>$multiref->product_id])->one();
							$productstd = ApplicationProductStandard::find()->where(['id'=>$Unitproduct->application_product_standard_id])->one();
						   if($productstd!==null)
							{
								   $standard_name = $productstd->standard->name;
								   $labelgradename = $productstd->label_grade_name;
								   $productname = $productstd->appproduct->product_name;

								   $producttypename = $productstd->appproduct->product_type_name;
								   $wastage = $productstd->appproduct->wastage;

								   $materialcompositionname = '';
								   if(count($productstd->productmaterial) >0){
									   foreach($productstd->productmaterial as $productmaterial){
										   $productMaterialList[]=[
											   'app_product_id'=>$productmaterial->app_product_id,
											   'material_id'=>$productmaterial->material_id,
											   'material_name'=>$productmaterial->material_name,
											   'material_type_id'=>$productmaterial->material_type_id,
											   'material_type_name'=> $productmaterial->material_type_name,//material->material_type[$productmaterial->material_type_id],
											   'material_percentage'=>$productmaterial->percentage
										   ];
											   $materialcompositionname = $materialcompositionname.$productmaterial->percentage.'% '.$productmaterial->material_name.' + ';
										   }
										   $materialcompositionname = rtrim($materialcompositionname," + ");
									   }
									$combined_product_name[] = $productname.' / '.$producttypename.' - '.$materialcompositionname.' <br>(Label Grade: '.$labelgradename.')';								
							   }
				   }
				   //$completepdtname=$combined_product_name;	
				   $completepdtname=implode(" <br> ",$combined_product_name);	

				}

				


				
				$materialused = [];
				$totalweightusedfrommaterial=0;
				$TcRawMaterialUsedWeight = TcRawMaterialUsedWeight::find()->where(['tc_request_product_id'=>$pdtdata->id])->all();
				if(count($TcRawMaterialUsedWeight)>0){
					foreach($TcRawMaterialUsedWeight as $UsedWeightObj){
						$totalweightusedfrommaterial+=$UsedWeightObj->used_weight;
						$materialused[] = [
							'stock_weight' => $UsedWeightObj->stock_weight,
							'used_weight' =>  $UsedWeightObj->used_weight,
							'remaining_weight' => $UsedWeightObj->remaining_weight,
							'supplier_name' => $UsedWeightObj->rawmaterial->supplier_name,							
							'tc_number' => $UsedWeightObj->rawmaterial->tc_number,
							'tc_attachment' => $UsedWeightObj->rawmaterial->tc_attachment,
							'invoice_attachment' => $UsedWeightObj->rawmaterial->tc_number==''?$UsedWeightObj->rawmaterial->invoice_attachment:'',
							'declaration_attachment' => $UsedWeightObj->rawmaterial->tc_number==''?$UsedWeightObj->rawmaterial->declaration_attachment:'',						
                                                        'raw_material_id' => $UsedWeightObj->tc_raw_material_id,
							'raw_material_product_id' => $UsedWeightObj->tc_raw_material_product_id,
							'trade_name' => $UsedWeightObj->rawmaterialproduct['trade_name'],
							'product_name' => $UsedWeightObj->rawmaterialproduct['product_name'],
							'lot_number' => $UsedWeightObj->rawmaterialproduct['lot_number'],
							'process_loss_percentage' => $UsedWeightObj->process_loss_percentage						
						];
					}
				}

				$rawmaterialusedlist = [];
				if(count($pdtdata->usedweight)>0){
					foreach($pdtdata->usedweight as $materialpdtused){
						$rawmaterialusedlist[]=[
							'tc_raw_material_id' => $materialpdtused->tc_raw_material_id,
							'tc_raw_material_product_id' => $materialpdtused->tc_raw_material_product_id,
							'product_id' => $materialpdtused->product_id,
							'stock_weight' => $materialpdtused->stock_weight,
							'used_weight' => $materialpdtused->used_weight,
							'remaining_weight' => $materialpdtused->remaining_weight,
							'process_loss_percentage' => $materialpdtused->process_loss_percentage,
							'process_loss_wastage_weight' => $materialpdtused->process_loss_wastage_weight,
							'rm_product_final_certified_weight' => $materialpdtused->rm_product_final_certified_weight

						];
					}
				}

				$TransportCompanyName=$pdtdata->transport_company_name;
				if($TransportCompanyName=='')
				{
					$TransportCompanyName='NA';
				}
				
				$VehicleContainerNo=$pdtdata->vehicle_container_no;
				if($VehicleContainerNo=='')
				{
					$VehicleContainerNo='NA';
				}
				$packedInUnitInfo = $pdtdata->packed_in;
				//$new_unitInfo = $pdtdata->unit_information;
				//print_r($new_unitInfo);
				/*				
				$unitInfo = $pdtdata->unit_information;
				if($unitInfo!='')
				{
					$packedInUnitInfo.= ' / '.$unitInfo;
				}
				*/
				
				$production_date_val='';
				if($pdtdata->production_date == null){
					$production_date_val= null;
				}else if($pdtdata->production_date != null){
					$production_date_val= date($date_format,strtotime($pdtdata->production_date));
				}
				//$pdtdata->packed_in
				$productdata[] = [
					'id' => $pdtdata->id,
					'tc_request_id' => $pdtdata->tc_request_id,
					'trade_name' => $pdtdata->trade_name,
					//'product_id' => $pdtdata->product_id,
					'product_id' => $productid,
					'product_name' => $completepdtname,
					'packed_in' => $packedInUnitInfo,
					'lot_ref_number' => $pdtdata->lot_ref_number,
					'consignee_id' => $pdtdata->consignee_id,
					'invoice_details' => 'Purchase Order No: '.$pdtdata->purchase_order_no.', Dt: '.date($date_format,strtotime($pdtdata->purchase_order_date)).', Invoice No: '.$pdtdata->invoice_no.', Dt: '.date($date_format,strtotime($pdtdata->invoice_date))
								.', Transport Document: '.$pdtdata->transport_document_no
								.', Dt: '.date($date_format,strtotime($pdtdata->transport_document_date))
								.', Transport Company Name:	'.$TransportCompanyName
								.', Vehicle / Container No: '.$VehicleContainerNo,
					'consignee_id'=>$pdtdata->consignee_id,
					'consignee_id_label'=>($pdtdata->consignee)?$pdtdata->consignee->name.' - '.$pdtdata->consignee->city:'',
					'consignee_address'=>($pdtdata->consignee)?$pdtdata->consignee->address:'',
					'consignee_license_number'=>($pdtdata->consignee)?$pdtdata->consignee->client_number:'',


					'unit_information' => $pdtdata->unit_information,
					'purchase_order_no' => $pdtdata->purchase_order_no,
					'purchase_order_date' => date($date_format,strtotime($pdtdata->purchase_order_date)),
					'invoice_no' => $pdtdata->invoice_no,
					'transport_document_no' => $pdtdata->transport_document_no,
					'transport_company_name' => $pdtdata->transport_company_name?:'NA',
					'vehicle_container_no' => $pdtdata->vehicle_container_no?:'NA',
					'invoice_date' => date($date_format,strtotime($pdtdata->invoice_date)),
					'transport_document_date' => date($date_format,strtotime($pdtdata->transport_document_date)),
					//'production_date'=>date($date_format,strtotime($pdtdata->production_date)),
					'production_date'=>$production_date_val,
 					'transport_id' => $pdtdata->transport_id,
 					'transport_id_label' => $pdtdata->transport?$pdtdata->transport->name:'',
					'wastage_percentage' => $pdtdata->wastage_percentage,
					'gross_weight' => $pdtdata->gross_weight,
					'net_weight' => $pdtdata->net_weight,
					'certified_weight' => $pdtdata->certified_weight,

					'std_1_certified_weight' => $pdtdata->std_1_certified_weight,
					'std_2_certified_weight' => $pdtdata->std_2_certified_weight,
						//'supplementary_weight' => $pdtdata->supplementary_weight?$pdtdata->supplementary_weight:'N/A',
						//'supplementary_weight' => $pdtdata->supplementary_weight,
						'supplementary_weight' => $pdtdata->supplementary_weight?$pdtdata->supplementary_weight:$pdtdata->additional_weight,


					'wastage_weight' => $pdtdata->wastage_weight,
					'additional_weight' => $pdtdata->additional_weight,
					'total_net_weight' => $pdtdata->total_net_weight,
					'total_used_weight' => $pdtdata->total_used_weight,
					'product_status'=> $pdtdata->status,
					'totalweightusedfrommaterial' => $totalweightusedfrommaterial,
					'materialused' => $materialused,
					'rawmaterialusedlist' => $rawmaterialusedlist
					
				];
			}
		}
		return $productdata;
	}
	public function actionRawmaterialgroup(){
	}
	
	public function actionGetFilterStatus()
    {
		$request = new Request();
		$arrayTCStatus = $request->arrStatus;
		$arrayTcEnumStatus = $request->arrEnumStatus;
		
		$userrole = Yii::$app->userrole;
		$user_type=$userrole->user_type;				
		$resource_access=$userrole->resource_access;
		$is_headquarters =$userrole->is_headquarters;						
		if($resource_access != '1' &&  $user_type!=Yii::$app->params['user_type']['customer'])
		{	
			unset($arrayTCStatus[0]);
			unset($arrayTCStatus[1]);
			
			unset($arrayTcEnumStatus['open']);
			unset($arrayTcEnumStatus['draft']);
		}		
		return ['statuslist'=>$arrayTCStatus,'enumstatus'=>$arrayTcEnumStatus,'invoice_type_list'=>$request->arrTCInvoices];
	}   
	
	public function actionGetInvoiceOptions()
    {
		$request = new Request();
		return ['optionlist'=>$request->arrInvoiceOptions];
	}  

    protected function findModel($id)
    {
        if (($model = Request::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
	
	public function actionGetStatus()
	{
		$data  = Yii::$app->request->post();
		if($data)
		{
			$Request = new Request();
			if($data['status']==  $Request->arrEnumStatus['review_in_process'])
			{
				$model = new RequestReviewerComment();
			}
			else
			{
				$model = new RequestFranchiseComment();
			}
			
			return ['data'=>$model->arrStatus];
		}
		
	}
	public function actionListStatus()
    {
		$request = new Request();
		return ['statuslist'=>$request->arrStatus,'enumstatus'=>$request->arrEnumStatus];
	}

	public function actionDeletedata()
	{
		$data = Yii::$app->request->post();
		$responsedata=array('status'=>1,'message'=>'Something went wrong');
		if($data)
		{			
			$id = $data['id'];
			$arrTcRawMaterialIDs = [];
			$RequestModel = Request::find()->where(['id'=>$id])->one();
			if($RequestModel!==null)
			{
				$candelete = $this->canDeleteTc($RequestModel);
				if(!$candelete){
					return $responsedata;
				}
				$TcRequestProductModel = RequestProduct::find()->where(['tc_request_id'=>$id])->all();
			 
				if(count($TcRequestProductModel)>0)
				{
					foreach($TcRequestProductModel as $productModel){
						if($RequestModel->status==$RequestModel->arrEnumStatus['rejected']){
							$TcRequestProductInputMaterial = TcRequestProductInputMaterial::find()->where(['tc_request_product_id'=>$productModel->id])->all();
							if(count($TcRequestProductInputMaterial)>0){
								foreach($TcRequestProductInputMaterial as $tcinput){
									$arrTcRawMaterialIDs[] = $tcinput->tc_raw_material_id;
								}
							}

							TcRawMaterialUsedWeight::deleteAll(['tc_request_product_id' => $productModel->id]);
							TcRequestProductInputMaterial::deleteAll(['tc_request_product_id' => $productModel->id]);	
							$productModel->delete();
						}else{
							$this->deleteRequestProductData($productModel->id);
						}
					}
				}

				if(count($RequestModel->evidence)>0)
				{
					$target_dir = Yii::$app->params['tc_files']."evidence_files/"; 
					foreach($RequestModel->evidence as $evidence)
					{
						Yii::$app->globalfuns->removeFiles($evidence->evidence_file,$target_dir);
						$evidence->delete();
					}
				}

				$arrTcRawMaterialIDs = array_unique($arrTcRawMaterialIDs);
				if(count($arrTcRawMaterialIDs)>0){
					$RawMaterialModel = new RawMaterial();						
					$RawMaterialModel->updateProductWeightRawMaterial($arrTcRawMaterialIDs);
				}				

				$RequestModel->delete();
			}	
		}
		return $responsedata=array('status'=>1,'message'=>'Data deleted successfully');
	}

	public function deleteRequestProductData($id){
		$responsedata=array('status'=>1,'message'=>'Something went wrong');
		$TcRequestProductModel = RequestProduct::find()->where(['id'=>$id])->one();			 
		if($TcRequestProductModel!== null)
		{
			$arrTcRawMaterialIDs = [];


			// Delete the Multiple entries 
			$multiple_entry= $TcRequestProductModel -> multiple_tc_id;

			if ($multiple_entry != null){
				RequestProductMultiple::deleteAll(['multiple_tc_id' => $multiple_entry]);
			}
			
			$req_id = $TcRequestProductModel -> id;
			if ($req_id != null){
				RawMaterialCertifiedWeight::deleteAll(['tc_request_product_id' => $req_id]);
			}

			// ------ Reduce the Weight from TC Request Code Start Here----------
			$requestObj = $TcRequestProductModel->request;
			if($requestObj!==null)
			{
				
				
				$TotalGrossWeight=0;
				$TotalNetWeight=0;
				$TotalCertifiedWeight=0;
				$TotalWastageWeight=0;
				$GrandTotalNetWeight=0;
				$GrandTotalUsedWeight=0;
				
				$TotalGrossWeight=$requestObj->total_gross_weight;
				$TotalNetWeight=$requestObj->total_net_weight;;
				$TotalCertifiedWeight=$requestObj->total_certified_weight;
				$TotalWastageWeight=$requestObj->total_wastage_weight;
				$GrandTotalNetWeight=$requestObj->grand_total_net_weight;
				$GrandTotalUsedWeight=$requestObj->grand_total_used_weight;
								
				$TotalGrossWeight=$TotalGrossWeight-$TcRequestProductModel->gross_weight;				
				$TotalNetWeight=$TotalNetWeight-$TcRequestProductModel->net_weight;
				$TotalCertifiedWeight=$TotalCertifiedWeight-$TcRequestProductModel->certified_weight;
				$TotalWastageWeight=$TotalWastageWeight-$TcRequestProductModel->wastage_weight;
				$GrandTotalNetWeight=$GrandTotalNetWeight-$TcRequestProductModel->total_net_weight;
				
				$GrandTotalUsedWeight=$GrandTotalUsedWeight-$TcRequestProductModel->total_used_weight;

				$requestObj->total_gross_weight=$TotalGrossWeight;
				$requestObj->total_net_weight=$TotalNetWeight;
				$requestObj->total_certified_weight=$TotalCertifiedWeight;
				$requestObj->total_wastage_weight=$TotalWastageWeight;
				$requestObj->grand_total_net_weight=$GrandTotalNetWeight;
				$requestObj->grand_total_used_weight=$GrandTotalUsedWeight;
				$requestObj->save();					
			}	
			// ------ Reduce the Weight from TC Request Code End Here----------
			
			$requestproductinputmaterialObj= $TcRequestProductModel->requestproductinputmaterial;
			if(count($requestproductinputmaterialObj)>0)
			{
				foreach($requestproductinputmaterialObj as $rpinputmaterial)
				{

					$RawMaterialProductUpdate = RawMaterialProduct::find()->where(['id'=>$rpinputmaterial->tc_raw_material_product_id])->one();
					if($RawMaterialProductUpdate !== null)
					{
						$net_weight = $RawMaterialProductUpdate->net_weight;
						$total_weight = $net_weight + $rpinputmaterial->used_weight;
						$RawMaterialProductUpdate->net_weight = $total_weight;
						$RawMaterialProductUpdate->total_used_weight = $RawMaterialProductUpdate->total_used_weight - $rpinputmaterial->used_weight;
						$RawMaterialProductUpdate->save();
					}

					$RawMaterialUpdate = RawMaterial::find()->where(['id'=>$rpinputmaterial->tc_raw_material_id])->one();
					if($RawMaterialUpdate !== null)
					{
						$arrTcRawMaterialIDs[] = $rpinputmaterial->tc_raw_material_id;
						/*
						if($RawMaterialUpdate->is_certified=="1"){
							$certified_weight = $RawMaterialUpdate->certified_weight;
							$total_weight = $certified_weight + $rpinputmaterial->used_weight;
							$RawMaterialUpdate->certified_weight = $total_weight;
						}else if($RawMaterialUpdate->is_certified=="2"){
							$net_weight = $RawMaterialUpdate->net_weight;
							$total_weight = $net_weight + $rpinputmaterial->used_weight;
							$RawMaterialUpdate->net_weight = $total_weight;
						}
						*/
						
						//$net_weight = $RawMaterialUpdate->net_weight;
						//$total_weight = $net_weight + $rpinputmaterial->used_weight;
						//$RawMaterialUpdate->net_weight = $total_weight;
						//$RawMaterialUpdate->total_used_weight = $RawMaterialUpdate->total_used_weight - $rpinputmaterial->used_weight;
						//$certified_weight = $RawMaterialUpdate->certified_weight;												
						//$total_weight = $certified_weight + $rpinputmaterial->used_weight;
						//$RawMaterialUpdate->certified_weight = $total_weight;
						//$RawMaterialUpdate->save();
					}
				}
			}	
			TcRawMaterialUsedWeight::deleteAll(['tc_request_product_id' => $id]);
			TcRequestProductInputMaterial::deleteAll(['tc_request_product_id' => $id]);	
			$TcRequestProductModel->delete();	


			$arrTcRawMaterialIDs = array_unique($arrTcRawMaterialIDs);
			$RawMaterialModel = new RawMaterial();						
			$RawMaterialModel->updateProductWeightRawMaterial($arrTcRawMaterialIDs);
		}
		return true;
	}

	public function actionDeleteproductdata()
	{
		$data = Yii::$app->request->post();
		if($data)
		{			
			$id = $data['id'];
			
			$this->deleteRequestProductData($id);
			 	
		}
		return $responsedata=array('status'=>1,'message'=>'Product deleted successfully');
	}
	
	public function actionProductwiserawmaterialinputs()
	{
		$data = Yii::$app->request->post();
		$userData = Yii::$app->userdata->getData();
		$userid=$userData['userid'];
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$certifiedinputerr=array('status'=>0,'message'=>'Please Update Stocks');
		if($data)
		{
			$tc_request_product_id = $data['tc_request_product_id'];			
			$RequestProduct = RequestProduct::find()->where(['id'=>$tc_request_product_id])->one();
			if($RequestProduct !== null)
			{
				$showedit= $this->canEditTc($RequestProduct->request);
				if($showedit==0)
				{
					return $responsedata;
				}
				
				///////////////////////////////////////////////////////////////////////////// New Certified Weight Logic Start Here; ///////////////////////////////////////////////////////////////////////////
				// if(isset($data['inputweight']) && count($data['inputweight'])>0 )
				// {

				// 	RawMaterialCertifiedWeight::deleteAll(['tc_request_product_id' => $tc_request_product_id]);
					
				// 	// To find the raw  material id  using the raw material product id 
				// 	$total_raw_material_net_weight = 0;
				// 	$total_raw_material_certified_weight =0;
				// 	$total_consumable_weight = 0;
				// 	$product_wastage_percentage = 0;
				// 	$new_certified_weight_calculated = 0;
				// 	$tc_request_id = '';
				// 	$raw_material_id ='';
				// 	foreach($data['inputweight'] as $inputmaterial)
				// 	{
				// 		$tc_raw_material_product = RawMaterialProduct::find()->where(['id'=>$inputmaterial['tc_raw_material_product_id']])->one();
				// 		if($tc_raw_material_product !== null)
				// 		{
				// 			$total_raw_material_net_weight = $tc_raw_material_product->actual_net_weight;
				// 			$total_raw_material_certified_weight = $tc_raw_material_product->certified_weight;
				// 			$raw_material_id = 	$tc_raw_material_product->raw_material_id;
				// 		}

				// 		$tc_req_product = RequestProduct::find()->where(['id'=>$tc_request_product_id])->one();
				// 		if($tc_req_product !== null){
				// 				$product_wastage_percentage=$tc_req_product->wastage_percentage;
				// 				$tc_request_id = 	$tc_req_product->tc_request_id;					 
				// 		}

				// 		$total_consumable_weight = $inputmaterial['rminputweight'];

				// 		if($total_raw_material_net_weight != 0 && $total_raw_material_certified_weight !=0 && $total_consumable_weight !=0 )
				// 		{
				// 			$new_certified_weight_calculated = number_format((($total_raw_material_certified_weight / $total_raw_material_net_weight * $total_consumable_weight )*(100-$product_wastage_percentage)/100),2);
				// 		}
                //             $temp_value =  floatval(preg_replace('/[^\d.]/', '', $new_certified_weight_calculated));
				// 			$model_new_certified_weight = new RawMaterialCertifiedWeight();
				// 			$model_new_certified_weight->tc_request_id = $tc_request_id;
				// 			$model_new_certified_weight->tc_request_product_id = $tc_request_product_id;
				// 			$model_new_certified_weight->tc_raw_material_id = $raw_material_id;
				// 			$model_new_certified_weight->tc_raw_material_product_id = $inputmaterial['tc_raw_material_product_id'];
				// 			$model_new_certified_weight->certified_weight = $temp_value;
				// 			$model_new_certified_weight->created_at = time();
				// 			$model_new_certified_weight->created_by = $userid;
				// 			$model_new_certified_weight->updated_at = time();
				// 			$model_new_certified_weight->updated_by = $userid;							
				// 			$model_new_certified_weight->save();
				// 	}
				// }		
				////////////////////////////////////////////////////////////////////////////// New Certified Weight Logic END Here; //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////			

					// if(isset($data['inputweight']) && count($data['inputweight'])>0 )
				// {
				// 	$errmaterialList=[];
				// 	foreach($data['inputweight'] as $inputmaterial)
				// 	{
				// 		if($inputmaterial['stdtype']!="non_standard" ){
				// 			$rawmaterialprodmaterial = RawMaterialProductMaterial::find()->where(['raw_material_product_id' => $inputmaterial['tc_raw_material_product_id']])->all();
				// 			if(count($rawmaterialprodmaterial) > 0)
				// 			{
				// 				foreach($rawmaterialprodmaterial as $rmpm)
				// 				{
				// 					if($rmpm['material_percentage']=== null || $rmpm['material_percentage']=='' || $rmpm['material_percentage']=='0.00')
				// 					{
				// 						if(! in_array($rmpm['raw_material_id'],$errmaterialList))
				// 						{
				// 							$errmaterialList[]= $rmpm['raw_material_id'];
				// 						}
				// 					}
				// 				}
				// 			}else{
				// 				$rawmaterialprod = RawMaterialProduct::find()->where(['id'=>$inputmaterial['tc_raw_material_product_id']])->one();
				// 				if($rawmaterialprod!==null){
				// 					if(! in_array($rawmaterialprod['raw_material_id'],$errmaterialList))
				// 					{
				// 						$errmaterialList[]= $rawmaterialprod['raw_material_id'];
				// 					}
				// 				}
				// 			}
				// 		}
				// 	}
				// 	if(count($errmaterialList)>0){
				// 		$rawmaterial = RawMaterial::find()->where(['in','id', implode(',',$errmaterialList)])->all();
				// 		if(count($rawmaterial) > 0){
				// 			$tcnumbers =[];
				// 			foreach($rawmaterial as $raw){
				// 				if($raw->is_certified==1){
				// 					$tcnumbers[] = $raw->tc_number;
				// 				}else if($raw->is_certified==3){
				// 					$tcnumbers[] = $raw->invoice_number;
				// 				}	
				// 			}
							
				// 		}
				// 		return array('status'=>0,'message'=>"Please Update the mentioned Raw Material's TCs(".implode(',',array_unique($tcnumbers)).") Certified Materials and Percentages");
				// 	}
				// }

				// Change Store the Newly calculated Certified Weight in the Table TC Request Product,
				if(isset($data['inputweight']) && count($data['inputweight'])>0 )
				{
					$final_certified_weight_product_wise = 0;
					foreach($data['inputweight'] as $inputmaterial)
					{
						if($inputmaterial['stdtype']!="non_standard" ){
							if($inputmaterial['rm_product_final_certified_weight'] != "")
							{
							$input_wise_final_certified_weight = $inputmaterial['rm_product_final_certified_weight'];
							$final_certified_weight_product_wise += $input_wise_final_certified_weight;
							}
							else if($inputmaterial['rm_product_final_certified_weight'] == "" || $inputmaterial['rm_product_final_certified_weight'] == null) {
								return $certifiedinputerr;
							}		
						}				
					}

					// Store the final Calculated weight in the  tc request product 
					$tc_req_product = RequestProduct::find()->where(['id'=>$tc_request_product_id])->one();
					if($tc_req_product !== null)
					{
						$RequestProduct->certified_weight = $final_certified_weight_product_wise;
						$RequestProduct->save();
					}	
				}	
				//// Condition to check weight in there for raw material starts
				$weightErrorList = [];
				if(isset($data['inputweight']) && count($data['inputweight'])>0 )
				{
					$rawMaterialWeightList = [];
					
					foreach($data['inputweight'] as $inputmaterial)
					{
						$cur_used_weight = 0;
						$TcRawMaterialUsedWeight = TcRawMaterialUsedWeight::find()->where(['tc_raw_material_product_id'=>$inputmaterial['tc_raw_material_product_id'], 'tc_request_product_id'=> $tc_request_product_id])->one();
						if($TcRawMaterialUsedWeight !== null){
							$cur_used_weight = $TcRawMaterialUsedWeight->used_weight;
						}						
						$rm_net_weight = 0;
						$RawMaterialProduct = RawMaterialProduct::find()->where(['id'=>$inputmaterial['tc_raw_material_product_id']])->one();
						if($RawMaterialProduct !== null){
							$rm_net_weight = $RawMaterialProduct->net_weight;
						}else{
							$weightErrorList[] = $inputmaterial['tc_raw_material_product_id'];
							$rawMaterialWeightList[$inputmaterial['tc_raw_material_product_id']] = [
								'net_weight'=>0,
								'supplier_name'=>'',
								'trade_name'=>'',
								'rawmaterial_standard_key'=>$inputmaterial['stdkey'],
								'rawmaterial_standard_type'=>$inputmaterial['stdtype'],
								'status' => 1
							];
						}

						$remaining_rm_net_weight = $cur_used_weight + $rm_net_weight - $inputmaterial['rminputweight'];
						if($remaining_rm_net_weight<0){
							$weightErrorList[] = $RawMaterialProduct->id;
							$rawMaterialWeightList[$RawMaterialProduct->id] = [
									'net_weight'=>$RawMaterialProduct->net_weight,
									'raw_material_id'=>$RawMaterialProduct->raw_material_id,
									'supplier_name'=>$RawMaterialProduct->rawmaterial->supplier_name,
									'trade_name'=>$RawMaterialProduct->trade_name,
									'rawmaterial_standard_key'=>$inputmaterial['stdkey'],
									'rawmaterial_standard_type'=>$inputmaterial['stdtype'],
									'status' => 0
								];
							//$rawMaterialWeightList[] = $RawMaterialProduct->net_weight;
						}


					}
					if(count($weightErrorList)>0){
						return ['status'=>2, 'weightErrorList'=>$weightErrorList,'rawMaterialWeightList'=>$rawMaterialWeightList];
					}
				}
				// condition to check weight ends here

				$TcRequestProductObj = new RequestProduct();
				$userData = Yii::$app->userdata->getData();
				$userid=$userData['userid'];							
				$arrTcRawMaterialIDs=array();		
				//--------- Update the Weight from Input Material to Raw Material Code Start Here ----------------			
				$TcRequestProductModel = RequestProduct::find()->where(['id'=>$tc_request_product_id])->one();
				if($TcRequestProductModel!== null)
				{
					$requestproductinputmaterialObj = $TcRequestProductModel->requestproductinputmaterial;
					if(count($requestproductinputmaterialObj)>0)
					{
						foreach($requestproductinputmaterialObj as $rpinputmaterial)
						{
							$RawMaterialUpdate = RawMaterialProduct::find()->where(['id'=>$rpinputmaterial->tc_raw_material_product_id])->one();
							if($RawMaterialUpdate !== null)
							{
								$arrTcRawMaterialIDs[] = $RawMaterialUpdate->raw_material_id;
								/*
								if($RawMaterialUpdate->is_certified=="1"){
									$certified_weight = $RawMaterialUpdate->certified_weight;
									$total_weight = $certified_weight + $rpinputmaterial->used_weight;
									$RawMaterialUpdate->certified_weight = $total_weight;
								}else if($RawMaterialUpdate->is_certified=="2"){
									$net_weight = $RawMaterialUpdate->net_weight;
									$total_weight = $net_weight + $rpinputmaterial->used_weight;
									$RawMaterialUpdate->net_weight = $total_weight;
								}
								*/
								
								$net_weight = $RawMaterialUpdate->net_weight;
								$total_weight = $net_weight + $rpinputmaterial->used_weight;
								$RawMaterialUpdate->net_weight = $total_weight;
								$RawMaterialUpdate->total_used_weight = $RawMaterialUpdate->total_used_weight - $rpinputmaterial->used_weight;
								$RawMaterialUpdate->save();
							}
						}
					}					
				}
				TcRawMaterialUsedWeight::deleteAll(['tc_request_product_id' => $tc_request_product_id]);
				TcRequestProductInputMaterial::deleteAll(['tc_request_product_id' => $tc_request_product_id]);
				TcRawMaterialUsedWeightWithBlended::deleteAll(['tc_request_product_id' => $tc_request_product_id]);			
				//--------- Update the Weight from Input Material to Raw Material Code End Here -----------------

				$RequestProduct = RequestProduct::find()->where(['id'=>$tc_request_product_id])->one();
				if($RequestProduct !== null)
				{
					$product_id = $RequestProduct->product_id;									
				}			
				
				
				$usedTotalRawMaterialWeight=0;
				if(isset($data['inputweight']) && count($data['inputweight'])>0 )
				{
					foreach($data['inputweight'] as $inputmaterial)
					{
						$RawMaterialProductObj = RawMaterialProduct::find()->where(['id'=>$inputmaterial['tc_raw_material_product_id']])->one();
						$RawMaterialProductObj->total_used_weight = $RawMaterialProductObj->total_used_weight + $inputmaterial['rminputweight'];
						$RawMaterialProductObj->save();

						$raw_material_id = $RawMaterialProductObj->raw_material_id;
						$arrTcRawMaterialIDs[] = $raw_material_id;
						
						$TcRawMaterialUsedWeight = TcRawMaterialUsedWeight::find()->where(['tc_raw_material_product_id'=>$inputmaterial['tc_raw_material_product_id'], 'tc_request_product_id'=> $tc_request_product_id])->one();
						if($TcRawMaterialUsedWeight === null){
							$TcRawMaterialUsedWeight = new TcRawMaterialUsedWeight();
							$TcRawMaterialUsedWeight->created_by = $userid;
						}else{
							$TcRawMaterialUsedWeight->updated_by = $userid;
						}
						
						$remaining_weight = 0;
						$RawMaterialProduct = RawMaterialProduct::find()->where(['id'=>$inputmaterial['tc_raw_material_product_id']])->one();
						if($RawMaterialProduct !== null){
							/*
							if($RawMaterial->is_certified=="1"){
								$certified_weight = $RawMaterial->certified_weight;
								$remaining_weight = $certified_weight  - $inputmaterial['rminputweight'];
								$RawMaterial->certified_weight = $remaining_weight;
							}else if($RawMaterial->is_certified=="2"){
								$net_weight = $RawMaterial->net_weight;
								$remaining_weight = $net_weight  - $inputmaterial['rminputweight'];
								$RawMaterial->net_weight = $remaining_weight;
								$certified_weight = $net_weight;
							}
							*/
							
							$net_weight = $RawMaterialProduct->net_weight;
							$remaining_weight = $net_weight  - $inputmaterial['rminputweight'];
							$RawMaterialProduct->net_weight = $remaining_weight;
							$certified_weight = $net_weight;
							
							//$certified_weight = $RawMaterial->certified_weight;												
							//$remaining_weight = $certified_weight - $inputmaterial['rminputweight'];
							//$RawMaterial->certified_weight = $remaining_weight;
							$RawMaterialProduct->save();
						}				

						$TcRawMaterialUsedWeight->tc_request_product_id = $tc_request_product_id;
						$TcRawMaterialUsedWeight->tc_raw_material_id = $raw_material_id;
						$TcRawMaterialUsedWeight->tc_raw_material_product_id = $inputmaterial['tc_raw_material_product_id'];
						$TcRawMaterialUsedWeight->used_weight = $inputmaterial['rminputweight'];
						
						$TcRawMaterialUsedWeight->product_id = $product_id;
						$TcRawMaterialUsedWeight->stock_weight = $certified_weight;
						$TcRawMaterialUsedWeight->process_loss_percentage = $inputmaterial['process_loss_percentage'];
						$TcRawMaterialUsedWeight->process_loss_wastage_weight = $inputmaterial['process_loss_wastage_weight'];
						if($inputmaterial['stdtype']!="non_standard"){
						
						$TcRawMaterialUsedWeight->rm_product_final_certified_weight = $inputmaterial['rm_product_final_certified_weight'];
						}

						$TcRawMaterialUsedWeight->remaining_weight = $remaining_weight;
						$TcRawMaterialUsedWeight->status = 0;
						if($TcRawMaterialUsedWeight->save())
						{
							$TcRequestProductInputMaterial = TcRequestProductInputMaterial::find()->where(['tc_raw_material_product_id'=>$inputmaterial['tc_raw_material_product_id'], 'tc_request_product_id'=> $tc_request_product_id])->one();
							if($TcRequestProductInputMaterial === null){
								$TcRequestProductInputMaterial = new TcRequestProductInputMaterial();
								//$TcRequestProductInputMaterial->created_by = $userid;
							}else{
								//$TcRequestProductInputMaterial->updated_by = $userid;
							}
							$TcRequestProductInputMaterial->tc_request_product_id = $tc_request_product_id;
							$TcRequestProductInputMaterial->tc_raw_material_id = $raw_material_id;
							$TcRequestProductInputMaterial->tc_raw_material_product_id = $inputmaterial['tc_raw_material_product_id'];
							$TcRequestProductInputMaterial->used_weight = $inputmaterial['rminputweight'];
							if($TcRequestProductInputMaterial->save())
							{
								$usedTotalRawMaterialWeight=$usedTotalRawMaterialWeight+$TcRequestProductInputMaterial->used_weight;
							}

							//Certifed Weight to be Stored with Blended Materials
							if($inputmaterial['stdtype']!="non_standard")
							{
								$materialtotalpercentage = RawMaterialProductMaterial::find()->where(['raw_material_product_id'=>$inputmaterial['tc_raw_material_product_id']])->andWhere(['material_type'=> 1])->Sum('material_percentage');

								$rawmaterialprodmat = RawMaterialProductMaterial::find()->where(['raw_material_product_id'=>$inputmaterial['tc_raw_material_product_id']])->andWhere(['material_type'=> 1])->all();
								if(count($rawmaterialprodmat) > 0)
								{
									foreach($rawmaterialprodmat as $mat)
									{
										$percentage = $mat['material_percentage'];
										$product_certified_weight = $inputmaterial['rm_product_final_certified_weight'];

										$material_certified_weight = ($product_certified_weight / $materialtotalpercentage) * $percentage;

										$rawmaterialusedweightbld = new TcRawMaterialUsedWeightWithBlended();
										$rawmaterialusedweightbld->tc_raw_material_used_weight_id = $TcRawMaterialUsedWeight->id;
										$rawmaterialusedweightbld->tc_raw_material_id = $raw_material_id;
										$rawmaterialusedweightbld->tc_raw_material_product_id =  $inputmaterial['tc_raw_material_product_id'];
										$rawmaterialusedweightbld->tc_request_product_id = $tc_request_product_id;
										$rawmaterialusedweightbld->material_certified_weight = $material_certified_weight;
										$rawmaterialusedweightbld->material_id = $mat['material_id'];
										$rawmaterialusedweightbld->material_percentage = $percentage;
										$rawmaterialusedweightbld->save();
									}
								}
							}
						}				
					}

					if($RequestProduct !== null)
					{
						$totalNetWeight = 0;
						$totalNetWeight = $RequestProduct->total_net_weight; 
						//$RequestProduct->total_used_weight = number_format($usedTotalRawMaterialWeight,2);
						$RequestProduct->total_used_weight = $usedTotalRawMaterialWeight;
						if($data['calculated_wastage_weight'] != "NaN" && $data['calculated_wastage_weight'] != "" && $data['calculated_wastage_weight'] !== null ){
							$RequestProduct->wastage_weight = $data['calculated_wastage_weight'];
						}
						
						if($RequestProduct->total_used_weight>=$totalNetWeight)
						{
							$RequestProduct->status = $RequestProduct->arrEnumStatus['input_added'];
						}else{
							$RequestProduct->status = $RequestProduct->arrEnumStatus['open'];
						}						
						$RequestProduct->save();
						
						$this->updateProductWeightToRequest($RequestProduct->tc_request_id);
					}
					
					if(count($arrTcRawMaterialIDs)>0)
					{
						$arrTcRawMaterialIDs = array_unique($arrTcRawMaterialIDs);
						$RawMaterialModel = new RawMaterial();						
						$RawMaterialModel->updateProductWeightRawMaterial($arrTcRawMaterialIDs);
					}
					$responsedata=array('status'=>1,'message'=>'Stock updated successfully');
				}
			}			
		}
		return $responsedata;
	}

	public function actionChangeStatus()
	{
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$data  = Yii::$app->request->post();
		$userrole = Yii::$app->userrole;
		$userid=$userrole->user_id;				
		$resource_access=$userrole->resource_access;		
		if($data)
		{
			$Request = Request::find()->where(['id'=>$data['id']])->one();
			if($Request !== null && $data['tc_status'] =='approval')
			{
				if($resource_access!=1)
				{
					if($Request->application->customer_id!=$userid)
					{
						return $responsedata;	
					}
				}
				$Request->status = $Request->arrEnumStatus['waiting_for_osp_review'];
				if($Request->save())
				{
					$responsedata=array('status'=>1,'message'=>"Submitted for approval successfully!");
				}
			}
			
		}
		return $responsedata;
	}
	public function actionAddOspReview()
    {
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$data = Yii::$app->request->post();
		$userrole = Yii::$app->userrole;
		$userid=$userrole->user_id;				
		$user_type=$userrole->user_type;
		$role=$userrole->role;
		$rules=$userrole->rules;
		$franchiseid=$userrole->franchiseid;		
		$resource_access=$userrole->resource_access;
		$is_headquarters =$userrole->is_headquarters;
		$role_chkid=$userrole->role_chkid;
		if($data)
		{
			$Request = Request::find()->where(['id' => $data['id']])->one();
			if($Request!==null)
			{
				if($user_type==Yii::$app->params['user_type']['user'] && $is_headquarters!=1)
				{
					if($Request->application->franchise_id!=$franchiseid)
					{
						return false;
					}								
				}
				
				if($user_type==Yii::$app->params['user_type']['user'] && !Yii::$app->userrole->hasRights(array('assign_as_oss_review_for_tc')))
				{
					return false;										
				}else if($user_type== Yii::$app->params['user_type']['franchise'] && $is_headquarters!=1){
					if($resource_access == '5'){
						$userid = $franchiseid;
					}				
					if($Request->application->franchise_id!=$userid)
					{
						return false;
					}						
				}				
				
				$ospreviewmodel = new RequestFranchiseComment();
				$ospreviewmodel->tc_request_id = $data['id'];
				$ospreviewmodel->status = $data['status'];
				$ospreviewmodel->comment = isset($data['comment'])?$data['comment']:'';
				$ospreviewmodel->created_by = $userrole->user_id;
				$ospreviewmodel->created_at = time();
				if($ospreviewmodel->validate() && $ospreviewmodel->save())
				{
					
					if($data['status']=='1')
					{
						$RequestReviewer = RequestReviewer::find()->where(['tc_request_id'=>$data['id']])->one();
						if($RequestReviewer!==null){
							$Request->status = $Request->arrEnumStatus['review_in_process'];//4;
						}else{
							$Request->status = $Request->arrEnumStatus['waiting_for_review'];//4;
						}
						
					}
					else if($data['status']=='2')
					{
						$Request->status = $Request->arrEnumStatus['pending_with_customer'];//1;
					}
					else if($data['status']=='3')
					{
						$Request->status = $Request->arrEnumStatus['rejected'];//7;
					}
					if($Request->save())
					{
						if($data['status']=='3')
						{
							$this->restoreRawMaterialWeight($ospreviewmodel->tc_request_id);
						}
						
						$responsedata=array('status'=>1,'message'=>"Review Saved Successfully!");
					}								
				}
			}		
		}
		return $responsedata;
	}

	public function actionAddReviewerReview()
    {
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$data = Yii::$app->request->post();
		$userrole = Yii::$app->userrole;
		$userid=$userrole->user_id;				
		$user_type=$userrole->user_type;
		$role=$userrole->role;
		$rules=$userrole->rules;
		$franchiseid=$userrole->franchiseid;		
		$resource_access=$userrole->resource_access;
		$is_headquarters =$userrole->is_headquarters;
		$role_chkid=$userrole->role_chkid;
		if($data)
		{
			$Request = Request::find()->where(['id' => $data['id']])->one();
			if($Request!==null)
			{
				if($resource_access!=1)
				{
					if($Request->reviewer===null || $Request->reviewer->user_id!=$userid)
					{
						return $responsedata;
					}
				}
				
				$ospreviewmodel = new RequestReviewerComment();
				$ospreviewmodel->tc_request_id = $data['id'];
				$ospreviewmodel->tc_request_reviewer_id = $userid;
				$ospreviewmodel->status = $data['status'];
				$ospreviewmodel->comment = isset($data['comment'])?$data['comment']:'';
				$ospreviewmodel->created_by = $userid;
				$ospreviewmodel->created_at = time();
				if($ospreviewmodel->validate() && $ospreviewmodel->save())
				{
					//$Request = Request::find()->where(['id' => $data['id']])->one();
					//if($Request!==null)
					//{
						if($data['status']=='1')
						{
							$Request->status =  $Request->arrEnumStatus['approved'];//6;						
							$tc_number = 3001;
							$RequestMax = Request::find()->where(['status'=>$Request->arrEnumStatus['approved'] ])->orderBy(['CAST(tc_number AS SIGNED INTEGER)' => SORT_DESC])->one();
							if($RequestMax!==null){
								if($RequestMax->tc_number>0){
									$tc_number = $RequestMax->tc_number + 1;
								}
							}
							$tc_number = str_pad($tc_number,6,"0",STR_PAD_LEFT );
							$Request->tc_number = $tc_number;
							$Request->tc_number_cds = $tc_number;
							//$Request->generateInvoice();
						}
						else if($data['status']=='2')
						{
							$Request->status =  $Request->arrEnumStatus['pending_with_osp'];//3;
						}
						else if($data['status']=='3')
						{
							$Request->status =  $Request->arrEnumStatus['rejected'];//7;
						}
						if($Request->save())
						{
							if($Request->status==$Request->arrEnumStatus['approved'])
							{
								// ----------------------- Generate TC Code Start Here --------------------------
								$this->generateTC($data['id'],true);
								// ----------------------- Generate TC Code End Here ----------------------------
							}	
							
							if($data['status']=='3')
							{
								$this->restoreRawMaterialWeight($ospreviewmodel->tc_request_id);
							}
							$responsedata=array('status'=>1,'message'=>"Review Saved Successfully!");
						}
						
					//}
					
				}
			}	
			
		}
		return $responsedata;
	}

	public function actionWithdrawn(){
		$data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
        $requestw = Request::find()->where(['id' => $data['id']])->one();
		if($requestw!==null){
			$requestw->wcomment = isset($data['wcomment'])?$data['wcomment']:'';
			$requestw->status = $requestw->arrEnumStatus['withdrawn'];
			if($requestw->save()){
				$responsedata=array('status'=>1,'message'=>'Withdrawn has been updated successfully');	
			}
		}
		return $responsedata;
	}

	public function generateTC($requestID,$returnType=false)
	{
		// ----------------------- Generate TC Code Start Here --------------------------
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		$modelRequest = new Request();
							
		$html='';
		$model = Request::find()->where(['id' => $requestID]);
		$model = $model->andWhere(['not in','status',array($modelRequest->arrEnumStatus['open'],$modelRequest->arrEnumStatus['rejected'])]);
		$model = $model->one();
		if($model !== null)
		{
			$ospnumber = $model->application->franchise->usercompanyinfo->osp_number;
			$customeroffernumber = $model->application->customer->customer_number;
			
			$declaration = $model->declaration;
			$additional_declaration = $model->additional_declaration;
			$standard_declaration = $model->standard_declaration;
			$comments = $model->comments;
						
			// Tc Type 
			$sel_tc_type = $model->sel_tc_type;

			$sender_label ='';
			$reciever_label = '';

			if($sel_tc_type == 1){
				$sender_label = "Seller";
				$reciever_label = "Buyer";
			}
			else if($sel_tc_type == 2){
				$sender_label = "Sender";
				$reciever_label = "Receiver";
			}else if ($sel_tc_type == null || $sel_tc_type == ''){
				$sender_label = "Seller";
				$reciever_label = "Buyer";
			}
			
			// ----------- Getting the company name latest code start here  ----------------------
			$applicationCompanyName='';
			$applicationCompanyAddress='';
			$applicationCompanyUnitName='';
			$applicationCompanyUnitAddress='';
			$LastProcessorCountry= '';
			$LastProcessorDetails = '';
			$LastProcessorDetailsLicense = '';
			
			$LastProcessorCompany = '';
			
			$applicationModelObject = $model->applicationaddress;
			if($model->tc_type == 1){
				$applicationCompanyName=$applicationModelObject->company_name ;
				//$applicationCompanyAddress=$applicationModelObject->address.', '.$applicationModelObject->city.', '.$applicationModelObject->state->name.', '.$applicationModelObject->country->name.' - '.$applicationModelObject->zipcode;
				$applicationCompanyAddress=$applicationModelObject->address;
				$applicationCompanyAddressTownPostcode= $applicationModelObject->city.','.$applicationModelObject->zipcode;
				$applicationCompanyAddressStateCountry= $applicationModelObject->state->name.','.$applicationModelObject->country->name;

			}
			else if($model->tc_type == 2){
				$facilityModelObject = $model->facilityaddress;
				$applicationCompanyName=$facilityModelObject->name ;
				//$applicationCompanyAddress=$facilityModelObject->address.', '.$facilityModelObject->city.' - '.$facilityModelObject->state->name.', '.$facilityModelObject->country->name.' - '.$facilityModelObject->zipcode;;
				$applicationCompanyAddress=$facilityModelObject->address;
				$applicationCompanyAddressTownPostcode= $facilityModelObject->city.','.$facilityModelObject->zipcode;
				$applicationCompanyAddressStateCountry= $facilityModelObject->state->name.','.$facilityModelObject->country->name;			
				
			}			
			$applicationUnitModelObject = $model->applicationunit;
			$LastProcessorCountry = $model->applicationunit->country->name;
			if($model->sel_lastpro_info == 1)
			{
				if($applicationUnitModelObject->unit_type==1)
				{
					$applicationCompanyUnitName=$applicationModelObject->unit_name;

					$LastProcessorDetails = '
					<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Last Processor :</td>
					<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$applicationCompanyUnitName.'</td>
					';

					$LastProcessorDetailsLicense = '
					<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">- License No.:</td>
					<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$customeroffernumber.'</td>
					';
					
					$LastProcessorCompany ='
					 <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">- Country: </td>
					 <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$LastProcessorCountry.'</td>
					';

				} 
				else 
				{
					$applicationCompanyUnitName=$model->applicationunit->name;
					$LastProcessorDetails = '
					<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Last Processor :</td>
					<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$applicationCompanyUnitName.'</td>
					';

					$LastProcessorDetailsLicense = '
					<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">- License No.:</td>
					<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$customeroffernumber.'</td>
					';
					
					$LastProcessorCompany ='
					 <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">- Country: </td>
					 <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$LastProcessorCountry.'</td>
					';

				}
			} else {
				
					$LastProcessorDetails = '
					<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Last Processor :</td>
					<td style="width: 30%;border: none;" class="reportDetailLayoutInner"> - UnDisclosed</td>
					';
					
				$LastProcessorCompany ='
					 <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Last Processor Country: </td>
					 <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$LastProcessorCountry.'</td>
					';
			}
			
			
			



				// OCS additinal Decalaration 

				$ocs_additional_decalartion='efw';
				$tc_ocs_ifoam_standards=array();


				$IfoamStandard = TcRequestIfoamStandard::find()->where(['tc_request_id'=>$requestID])->all();


				if(count($IfoamStandard)>0)
				{
					foreach($IfoamStandard as $data)
					{
						$tc_ocs_ifoam_standards[]=$data->ifoamstdname?$data->ifoamstdname->name:'';
						
						
					}
				}
				$ocs_additional_decalartion=(implode(", ",$tc_ocs_ifoam_standards));
	

			// if($applicationUnitModelObject->unit_type==1)
			// {
			// 	$applicationCompanyUnitName=$applicationModelObject->unit_name;
			// 	$applicationCompanyUnitAddress=$applicationModelObject->unit_address.', '.$applicationModelObject->city.', '.$applicationModelObject->state->name.', '.$applicationModelObject->country->name.' - '.$applicationModelObject->zipcode;
		         
			// 	$LastProcessorCountry = $model->applicationunit->country->name;
		
			// }else{
			// 	$applicationCompanyUnitName=$model->applicationunit->name;
			// 	$applicationCompanyUnitAddress=$model->applicationunit->address.', '.$model->applicationunit->city.', '.$model->applicationunit->state->name.', '.$model->applicationunit->country->name.' - '.$model->applicationunit->zipcode;
			
			// 	$LastProcessorCountry = $model->applicationunit->country->name;

			// }	
			// ----------- Getting the company name latest code end here  ----------------------				
			
			$buyer = $model->buyer;
			$consignee = '';
			$inspection = $model->inspectionbody;
			$certification = $model->certificationbody;
			$total_certified_weight = $model->total_certified_weight;
			$total_gross_weight = $model->total_gross_weight;
			$total_net_weight = $model->total_net_weight;
			$grand_total_net_weight = $model->grand_total_net_weight;

			$usda_nop = ($model->usda_nop_compliant==1? "Yes" : "No" );
			
			$TransactionCertificateNo='';
			
			$draftText='';
			if($model->status!=$modelRequest->arrEnumStatus['approved'] && $requestID!=638 && $requestID!=1505 && $requestID!=1693)
			{
				$draftText='DRAFT ';
				$TransactionCertificateNo=$model->id;
			}else{
				$TransactionCertificateNo=$model->tc_number;
			}
			$raw_matCnt=1;
			$raw_material_tc_no='';
			$raw_material_farm_sc_no='';
			$raw_material_farm_tc_no='';
			$raw_material_trader_tc_no='';
			$arrRawMaterialTCNos=array();
			$arrRawMaterialIDs=array();
			$requestProdIds=array();
			$arrRawMaterialFarmSCNos=array();
			$arrRawMaterialFarmTCNos=array();
			$arrRawMaterialTraderTCNos=array();
			$requestProducts = $model->product;
			if(count($requestProducts)>0)
			{
				foreach($requestProducts as $requestProduct)
				{
					$requestProductInput = $requestProduct->requestproductinputmaterial;
					if(count($requestProductInput)>0)
					{
						foreach($requestProductInput as $productInput)
						{	
							$RawMaterialObj = $productInput->rawmaterial;
							$tcN = $RawMaterialObj->tc_number;
							if($tcN!='')
							{
								$arrRawMaterialTCNos[]=$tcN;
							}
                            else if($RawMaterialObj->is_certified==3){
								$arrRawMaterialTCNos[]="Not Applicable";
							}
							
							$farmScN = $RawMaterialObj->form_sc_number;
							if($farmScN!='')
							{
								$arrRawMaterialFarmSCNos[]=$farmScN;
							}else {
								$arrRawMaterialFarmSCNos[]="Not Applicable";
							}
							
							$farmTcN = $RawMaterialObj->form_tc_number;
							if($farmTcN!='')
							{
								$arrRawMaterialFarmTCNos[]=$farmTcN;
							}else {
								$arrRawMaterialFarmTCNos[]="Not Applicable";
							}
							
							$traderTcN = $RawMaterialObj->trade_tc_number;
							if($traderTcN!='')
							{
								$arrRawMaterialTraderTCNos[]=$traderTcN;
							}else {
								$arrRawMaterialTraderTCNos[]="Not Applicable";
							}								
						}
					}	
				}
			}
			$raw_material_tc_no=implode(", ", array_unique($arrRawMaterialTCNos));
			$raw_material_farm_sc_no=implode(", ",array_unique($arrRawMaterialFarmSCNos));
			$raw_material_farm_tc_no=implode(", ",array_unique($arrRawMaterialFarmTCNos));
			$raw_material_trader_tc_no=implode(", ",array_unique($arrRawMaterialTraderTCNos));
			
			$tc_generate_date = date('Y-m-d',time());
			// Subcontractor Declaration
			$Sub_contractor_declaration = "";
			$is_subcontractor_declared = RequestEvidence::find()->where(['tc_request_id'=>$requestID, 'evidence_type'=>'product_handled_by_subcontractor'])->one();
			if($is_subcontractor_declared!==null)
			{
				$Sub_contractor_declaration = "<span>&#x2611</span>Yes<span>&#9744</span> No";
			} else if($is_subcontractor_declared == null)
			{
				$Sub_contractor_declaration = "<span>&#9744</span>Yes<span>&#x2611</span> No";
			}
						
			$RegistrationNoArray=array();
			$RegistrationNoShortArray=array();
			
			$arrTcLogo=array();
			$tc_header_standard_title='';
			$tc_header_standard_title_other_page='';
			$tc_std_code='';
			$tc_std_name='';
			$tc_std_licence_number='';
			$tc_std_code_array=array();
			$tc_std_name_array=array();
			$tc_std_license_number_array=array();
			$std_licence_number = '';
			$show_additional_declarations = 0;
			if(count($model->standard)>0){
				foreach($model->standard as $reqstandard){
					$std_licence_number.= $reqstandard->standard->license_number."<br>";
					
					$standardCode = $reqstandard->standard->code;
					$tc_std_code_array[]=$standardCode;
					$tc_std_name_array[]=$reqstandard->standard->name;
					$tc_std_license_number_array[]=$reqstandard->standard->license_number;
					$standard_code_lower = strtolower($standardCode);
					$standardScode = $reqstandard->standard->short_code;
					
						//$RegistrationNoArray[] = "GCL-".$ospnumber.$standardScode.$customeroffernumber.'/'.$ospnumber.$standardCode.'-'.$TransactionCertificateNo;
					//$RegistrationNoArray[] = "GCL-".$customeroffernumber.'/'.$ospnumber.$standardCode.'-'.$TransactionCertificateNo;
					//$RegistrationNoArray[] =  $ospnumber.$standardCode.'-'.$TransactionCertificateNo;

					$RegistrationNoArray[] = $TransactionCertificateNo;
					$RegistrationNoShortArray[] = "GCL-".$ospnumber.$standardScode.$customeroffernumber;
					
					if($standard_code_lower=='gots' || $standard_code_lower=='grs'  || $standard_code_lower=='rds' || $standard_code_lower=='rws' || $standard_code_lower=='rms' )
					{
						$arrTcLogo[]=$standard_code_lower.'_logo.png';
						
					}
					if($standard_code_lower=='gots' || $standard_code_lower=='ocs')
					{
						$show_additional_declarations = 1;
					}
				}
			}
			$tc_std_code=implode(",",$tc_std_code_array);
			
			if($tc_std_code == "GOTS"){
				$GotsLabel ="For directions on how to authenticate this certificate, please visit GOTS' web page 'Approved Certification Bodies";
                $product_label_grade_lbl ="Label Grade:";
			}else {
				$GotsLabel = null;
                $product_label_grade_lbl ="Standard (Label Grade):";
			}
			
			$tc_std_name=strtoupper(implode(", ",$tc_std_name_array));			
			for ($index = 0; $index < count($tc_std_name_array); $index++) {
				$arr[$index] = $tc_std_name_array[$index]."(".$tc_std_code_array[$index].")";
			}
			$tc_header_standard_title = implode(",<br>", $arr);	
			if($tc_std_code == "GOTS"){
				$tc_header_standard_title_other_page = "";
			}else {
				$tc_header_standard_title_other_page = implode(",<br>", $arr);
			}			
			// if(is_array($tc_std_name_array) && count($tc_std_name_array)>1)
			// {
			// 	$tc_std_name='MULTIPLE TEXTILE EXCHANGE STANDARD';	
			// }
			if( count($tc_std_name_array) > 1){
				$header_padding='padding-top:2px;';
				$header_font_size ='font-size:12px;';
			}else {
				$header_padding='padding-top:15px;';
				$header_font_size ='font-size:14px;';
			}
			
			$tc_std_licence_number=implode(", ",$tc_std_license_number_array);						
						
			$tc_sc_number_array=array();
			$tc_scope_licence_number='';
			if(count($model->standard)>0){
				foreach($model->standard as $reqstandard){
					$tc_sc_number_data = Certificate::find()->where(['parent_app_id' => $model->app_id,'standard_id' => $reqstandard->standard_id,'status'=>2 ])
					->orderBy('id DESC')
					->one();
					$customeroffernumber = $tc_sc_number_data->application->customer->customer_number;
					$standardCode = $tc_sc_number_data->standard->code;
					$tc_sc_number_array[]= "GCL-".$customeroffernumber.'-'.$standardCode;
				}
				$tc_scope_licence_number=implode(", ",$tc_sc_number_array);	
			}

			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
			header('Access-Control-Max-Age: 1000');
			header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

			$mpdf = new \Mpdf\Mpdf(array('mode' => 'utf-8','margin_left' => 10,'margin_right' => 10,'margin_top' => 24,'margin_bottom' => 12,'margin_header' => 0,'margin_footer' => 3,'setAutoTopMargin' => 'stretch','setAutoBottomMargin' => 'stretch'));
			$mpdf->SetDisplayMode('fullwidth');
			
			$qrCodeURL=Yii::$app->params['certificate_file_download_path'].'scan-transaction-certificate?code='.md5($model->id);
			if($draftText!='' && $requestID!=638 && $requestID!=1505 && $requestID!=1693)
			{
				$mpdf->SetWatermarkText('DRAFT');
				$mpdf->showWatermarkText = true;
				
				$qrCodeURL=Yii::$app->params['qrcode_scan_url_for_draft'];				
			}
															
			$qr = Yii::$app->get('qr');
			//Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;				
			//  $qrCodeContent=$qr->setText($qrCodeURL)			
			//  ->setLogo(Yii::$app->params['image_files']."qr-code-logo.png")			
			//  ->setLogoWidth(85)			
			//  ->setEncoding('UTF-8')
			//  ->writeDataUri();			
			/*
			$mpdf->SetWatermarkImage(Yii::$app->params['image_files'].'tc_bg.png',0.2);
			$mpdf->showWatermarkImage = true;
			*/			
			$DatePlaceContent='Place and Date of Issue <br>London, '.$tc_generate_date.'<br><br>';
			$logoStyle='padding-top:16px;';
			$TcLogoContent='';
             $standards_logos='';
			// Standard Logo		
			
			$html='
			<style>
			table {
				border-collapse: collapse;
			}						
			
			@page :first {
				header: html_firstpage;				
			}
						
			@page { 
				margin-top: 8%;
				margin-bottom: 15%;
				border: 1px black solid;
				footer:html_htmlpagefooter;
				background: url('.Yii::$app->params["image_files"].'gcl-bg.jpg) no-repeat 0 0;
				background-image-resize: 6;
				header: html_otherpageheader;			
			}		
			
			table, td, th {
				border: 0.5px solid black;
			}
			
			standardcertifiedweight {
				display: inline-block
			}
						
			table.reportDetailLayout {				
				border-collapse: collapse;
				width:100%;
				font-size:12px;
				font-family:Arial;
				text-align: left;
				margin-top:5px;
			}
			
			td.reportDetailLayout {
				text-align: center;
				border: 1px solid #000000;
				font-size:12px;
				font-family:Arial;
				padding:3px;
			}
			linebreak {
				border-top: 1px solid red;
    			width: 100%;
    			height: 50%;
    			position: absolute;
    			bottom: 0;
    			left: 0;
			  }

			td.reportDetailLayoutInner {
				font-size:12px;
				font-family:Arial;
				text-align: left;			
				padding:3px;
				vertical-align:top;
			}
			
			td.reportDetailLayoutInnerWithoutBorder {	
				border:none;
				font-size:12px;
				font-family:Arial;
				text-align: left;			
				vertical-align:top;
			}
			
			.innerTitleMain
			{
				color:#000000;
				font-size:12px;
				font-family:Arial;
				text-align: left;
				margin-bottom:5px;
				font-weight:bold;
			}
			.innerTitle
			{
				color:#000000;
				font-size:12px;
				font-family:Arial;
				text-align: left;
				margin-bottom:5px;
			}
			div.reportDetailLayoutInner {			
				font-size:12px;
				font-family:Arial;
				text-align: left;				
				padding:3px;
				vertical-align:top;
			}
			</style>
			
		
						
			
			<htmlpageheader name="firstpage" style="display:none;">
				<div style="width:100%;font-size:12px;font-family:Arial;position: absolute;margin-bottom: 75px;">
					<table cellpadding="0" cellspacing="0" border="0" width="100%"  style="border:none;">
						<tr>					    
						<td class="reportDetailLayoutInner" style="width:80%;text-align: center;border:none;'.$header_padding.';">  <span style="font-size:18px;font-weight:bold;">'.$draftText.' TRANSACTION CERTIFICATE (TC) </span> <br> <span style="font-size:14px;">Transaction Certificate Number '.$TransactionCertificateNo.' </span> <br>  <span style="font-size:12px;"> for products certified to </span> <br> <span style="'.$header_font_size.'">  '.$tc_header_standard_title.' </span> </td>
						<td class="reportDetailLayoutInner" style="width:20%;font-size:16px;font-weight:bold;text-align: center;border:none;"><img src="'.$qrCodeContent.'" style="width:85px;margin-right: 72px;"></td>
						</tr>								
					</table>
				</div>
			</htmlpageheader>
			
			<htmlpageheader name="otherpageheader" style="display:none;margin-top: 3cm;">
			<div style="width:80%;float:left;font-size:12px;font-family:Arial;position: absolute;text-align: center;padding-top:20px;">
					<span style="font-weight:bold;">Transaction Certificate Number '.$TransactionCertificateNo.' (continued)  </span> <br> <span style="font-size:14px;">'.$tc_header_standard_title_other_page.' </span>
				</div>
				<div style="width:20%;float:right;font-size:12px;font-family:Arial;position: absolute;left:630px;top:0px;padding-top:3px;margin-bottom: 85px;">
					<img src="'.$qrCodeContent.'" style="width:85px;margin-left: 42px;">
				</div>					
			</htmlpageheader>';
			
			// -------------- TC Product Code Start Here ------------------------
			$TcProductContent='';
			$TcProductContent='
			<table width="100%"   cellspacing="0" cellpadding="0" style="">';
			
			$TcShipmentContent='';	
			
			$TcShipmentContent = '
			<table width="100%" cellspacing="0" cellpadding="0" style="">';

			$TCCertifiedRawMaterials='';	
			
			$TCCertifiedRawMaterials = '
			<table width="100%" cellspacing="0" cellpadding="0" style="">';
												
				$productStandardArray=array();
				$productStandardCheckArray=array();
				$labelGradeCnt=1;
				$arrLabelGrade=array();
				if(count($requestProducts)>0)
				{
					$prtCnt=1;
					foreach($requestProducts as $requestProduct)
					{
						$productname = '';
						$completepdtname = '';
						$product_code = '';
						$product_type_code = '';
						$combined_tc_standard_name=array();
						$combined_tc_label_grade_name=array();
						$product_material_compostions=array();
						$product_material_compostions_name ='';

						$label_grade_name_with_standard ='';
					
						if ($requestProduct->multiple_tc_id == null)
						{
							$Unitproduct = $requestProduct->unitproduct;
							if($Unitproduct!== null)
							{
								$productstd = $Unitproduct->product;
								if($productstd!==null)
								{
									$standard_name = $productstd->standard->name;
									$standard_code = $productstd->standard->code;
									$labelgradename = $productstd->label_grade_name;
	
									$productname = $productstd->appproduct->product_name;
									$producttypename = $productstd->appproduct->product_type_name;
	
										//$label_grade_name_with_standard = $labelgradename;
										$label_grade_name_with_standard = '<div> '.$standard_code.'  ('.$labelgradename.') </div>';								

										// Getting Product and Product Type Code 
										$product_code = $productstd->appproduct->product->code;
										$product_type_code = $productstd->appproduct->producttype->code;
		
									$productcode = $productstd->appproduct->product->code;
									$producttypecode = $productstd->appproduct->producttype->code;
	
									$wastage = $productstd->appproduct->wastage;
									$materialcompositionname = '';
									if(count($productstd->productmaterial) >0)
									{
										foreach($productstd->productmaterial as $productmaterial)
										{
											$productMaterialList[]=[
												'app_product_id'=>$productmaterial->app_product_id,
												'material_id'=>$productmaterial->material_id,
												'material_name'=>$productmaterial->material_name,
												'material_type_id'=>$productmaterial->material_type_id,
												'material_type_name'=> $productmaterial->material_type_name,//material->material_type[$productmaterial->material_type_id],
												'material_percentage'=>$productmaterial->percentage
											];
											$materialcompositionname = $materialcompositionname.$productmaterial->percentage.'% '.$productmaterial->material_name.' ('.$productmaterial->material->code.') + ';
									}
										$materialcompositionname = rtrim($materialcompositionname," + ");
										$product_material_compostions_name = $materialcompositionname;
									}
									$completepdtname = $productname.' ('.$productcode.') / '.$producttypename.' ('.$producttypecode.') - '.$materialcompositionname.' <br>(Label Grade: '.$labelgradename.')';
									
									// ------------- Code for Identify Standard Blended Logo Code Start Here -----------------
									$standard_code_lower = strtolower($standard_code);
									if(!in_array($standard_code_lower,$productStandardCheckArray))
									{
										$arrLabelGrade=array();
										$arrLabelGrade[$labelGradeCnt]=strtolower($labelgradename);												
										if(is_array($arrLabelGrade) && count($arrLabelGrade)>0)
										{
											$resArray = array_filter($arrLabelGrade, function($value) {
												return (strpos($value, 'blended') !== false || strpos($value, 'bl') !== false) ? true : false ;
											}); 										
											
											if(is_array($resArray) && count($resArray)>0)
											{
												$arrTcLogo[]=$standard_code_lower.'_blended_logo.png';
												$productStandardCheckArray[]=$standard_code_lower;
											}
										}
									}
									// ------------- Code for Identify Standard Blended Logo Code End Here -----------------		
	
									// ------------- Code for Identify Standard 100 Logo Code Start Here -------------------
									$standard_code_lower = strtolower($standard_code);
									if(!in_array($standard_code_lower,$productStandardArray))
									{
										$arrLabelGrade=array();
										$arrLabelGrade[$labelGradeCnt]=strtolower($labelgradename);												
										if(is_array($arrLabelGrade) && count($arrLabelGrade)>0)
										{
											$resArray = array_filter($arrLabelGrade, function($value) {
												return strpos($value, '100') !== false;
											}); 										
											
											if(is_array($resArray) && count($resArray)>0)
											{
												$arrTcLogo[]=$standard_code_lower.'_100_logo.png';
												$productStandardArray[]=$standard_code_lower;
											}
										}
									}
									// ------------- Code for Identify Standard 100 Logo Code End Here -----------------									
								}										
							}
						} 
						else 
						{
							$multi_tc_product_ref = RequestProductMultiple::find()->where(['multiple_tc_id'=>$requestProduct->multiple_tc_id])->all();
							$combined_product_name = array();
								
							foreach($multi_tc_product_ref as $key=>$multiref)
							{

							// To get application unit Product
								$Unitproduct = ApplicationUnitProduct::find()->where(['id'=>$multiref->product_id])->one();
								$productstd = ApplicationProductStandard::find()->where(['id'=>$Unitproduct->application_product_standard_id])->one();		
								if($productstd!==null)
									{
										$standard_name = $productstd->standard->name;
										$standard_code = $productstd->standard->code;
										$labelgradename = $productstd->label_grade_name;
										$combined_tc_standard_name[]=$standard_code;
										$combined_tc_label_grade_name[]=$labelgradename;


										 if(is_array($combined_tc_standard_name) && count($combined_tc_standard_name)>1)
										 {										
											for ($index = 0; $index < count($combined_tc_standard_name); $index++) {
												$arr[$index] = $combined_tc_standard_name[$index]."(".$combined_tc_label_grade_name[$index].")";
											}
											$label_grade_name_with_standard = implode(",", $arr);
										
										 }
										$productname = $productstd->appproduct->product_name;
										$producttypename = $productstd->appproduct->product_type_name;
                                        // Getting Product and Product Type Code 
								     	$product_code = $productstd->appproduct->product->code;
										 $product_type_code = $productstd->appproduct->producttype->code;
	 
										$productcode = $productstd->appproduct->product->code;
									    $producttypecode = $productstd->appproduct->producttype->code;

										$wastage = $productstd->appproduct->wastage;
										$materialcompositionname = '';
										if(count($productstd->productmaterial) >0)
											{
												foreach($productstd->productmaterial as $productmaterial)
												{
													$productMaterialList[]=[
													'app_product_id'=>$productmaterial->app_product_id,
													'material_id'=>$productmaterial->material_id,
													'material_name'=>$productmaterial->material_name,
													'material_type_id'=>$productmaterial->material_type_id,
													'material_type_name'=> $productmaterial->material_type_name,//material->material_type[$productmaterial->material_type_id],
													'material_percentage'=>$productmaterial->percentage
													];
													$materialcompositionname = $materialcompositionname.$productmaterial->percentage.'% '.$productmaterial->material_name.' ('.$productmaterial->material->code.') + ';
												}
												$materialcompositionname = rtrim($materialcompositionname," + ");
												$product_material_compostions[]=$materialcompositionname;

												$product_material_compostions_name = implode(",", $product_material_compostions);
												
											}
											//$productmaterial->material->code
											$combined_product_name[] = '<div style="padding-top:10px"></div>'.$productname.' / '.$producttypename.' - '.$materialcompositionname.' <br>(Label Grade: '.$labelgradename.')';								
										}

										// ------------- Code for Identify Standard Blended Logo Code Start Here -----------------
								$standard_code_lower = strtolower($standard_code);
								if(!in_array($standard_code_lower,$productStandardCheckArray))
								{							
									$arrLabelGrade=array();
									$arrLabelGrade[$labelGradeCnt]=strtolower($labelgradename);												
									if(is_array($arrLabelGrade) && count($arrLabelGrade)>0)
									{
										$resArray = array_filter($arrLabelGrade, function($value) {
											return (strpos($value, 'blended') !== false || strpos($value, 'bl') !== false) ? true : false ;
										}); 										
										
										if(is_array($resArray) && count($resArray)>0)
										{
											$arrTcLogo[]=$standard_code_lower.'_blended_logo.png';
											$productStandardCheckArray[]=$standard_code_lower;
										}
									}
								}
								// ------------- Code for Identify Standard Blended Logo Code End Here -----------------		

								// ------------- Code for Identify Standard 100 Logo Code Start Here -----------------
								$standard_code_lower = strtolower($standard_code);
								if(!in_array($standard_code_lower,$productStandardArray))
								{
									$arrLabelGrade=array();
									$arrLabelGrade[$labelGradeCnt]=strtolower($labelgradename);												
									if(is_array($arrLabelGrade) && count($arrLabelGrade)>0)
									{
										$resArray = array_filter($arrLabelGrade, function($value) {
											return strpos($value, '100') !== false;
										}); 										
										
										if(is_array($resArray) && count($resArray)>0)
										{
											$arrTcLogo[]=$standard_code_lower.'_100_logo.png';
											$productStandardArray[]=$standard_code_lower;
										}
									}
								}
								// ------------- Code for Identify Standard 100 Logo Code End Here -----------------
							}
								$completepdtname=implode("  <br> ",$combined_product_name);
						}
											
						$requestProductInput = $requestProduct->requestproductinputmaterial;
					    if(count($requestProductInput)>0)
						{
							foreach($requestProductInput as $productInput)
						{	
							$RawMaterialObj = $productInput->rawmaterial;
							$tcN = $RawMaterialObj->tc_number;
							$CertWeight = $RawMaterialObj->certified_weight;
							if($tcN!='' || $RawMaterialObj->is_certified==3)
							{
								$RawMaterialNameWithCode='NA';
								$country_code_print = '';
								$state_code_print = '';
								$RawMaterialName=$tcN;
                                $CertifiedWeight=$CertWeight;
								// $Country =($RawMaterialObj->country_id!="")?$RawMaterialObj->country->name:"Geographic origin of raw materials not specified on incoming TC";
								// $State =($RawMaterialObj->state_id!="")?$RawMaterialObj->state->name:"";
								//$RawMaterialName =($RawMaterialObj->rawmaterial_name_id!="")?$RawMaterialObj->rawmaterialname->name:"";
								//$RawMaterialCode =($RawMaterialObj->rawmaterial_name_id!="")?$RawMaterialObj->rawmaterialname->code:"N/A";
								
								// $RawMaterialNameCode = $productInput->rawmaterialproduct;
                                // $RawMaterialProductMaterial = $RawMaterialNameCode->rawmaterialproductmaterial;

								// if(count($RawMaterialProductMaterial)>0)
								// {
								// 	$rawmaterialproductmaterial = [];
								// 	foreach($RawMaterialProductMaterial as $rmpm){
								// 		$rawmaterialproductmaterial[]= $rmpm->material['name'].'('.$rmpm->material['code'].')';
								// 	}
								// 	$RawMaterialNameWithCode = implode(',',$rawmaterialproductmaterial);
								// }
								
								////////////////////////////////////////////////// New Certified Weight Logic Start Here; /////////////////////////////////////////////////////////
								// $new_format_certified_weight = 0;
								// $tc_raw_material_product_id = $productInput->rawmaterialproduct->id;
								// $tc_request_product_id = $requestProduct->id;
								// //RawMaterialCertifiedWeight
								// $raw_material_certified_weight = RawMaterialCertifiedWeight::find()->where(['tc_raw_material_product_id'=>$tc_raw_material_product_id,'tc_request_product_id'=>$tc_request_product_id])->one();
								// if($raw_material_certified_weight !== null)
								// {
								// 	$new_format_certified_weight = $raw_material_certified_weight->certified_weight;
								// }
								////////////////////////////////////////////////// New Certified Weight Logic End Here; /////////////////////////////////////////////////////////////




								// $RawMaterialName =($RawMaterialNameCode->rawmaterial_name_id!="")?$RawMaterialNameCode->rawmaterialname->name:"";
								// $RawMaterialCode =($RawMaterialNameCode->rawmaterial_name_id!="")?$RawMaterialNameCode->rawmaterialname->code:"N/A";

								
							
								
						
                                if(! in_array($RawMaterialObj->id,$arrRawMaterialIDs))
								{					
									$arrRawMaterialIDs[]=$RawMaterialObj->id;
								}
								$raw_matCnt++;
							}							
						  }
						}
						// Supplimentry Weight 
						//.number_format($requestProduct->supplementary_weight,1).
						if($requestProduct->supplementary_weight == null){
							$supplimentary_weight = "N/A";
						}else {
							$supplimentary_weight = number_format($requestProduct->supplementary_weight,2).' kg';
						}
						//$production_date =($requestProduct->production_date!="")?$requestProduct->production_date:"N/A";
						
						
						$production_date =($requestProduct->production_date!="")?$requestProduct->production_date:null;


						if($production_date != null){
							$production_date_print = '
							<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Production Date: </td>
							<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$production_date.'</td>
							';
						}else if($production_date == null) {
							$production_date_print = '';
						}

						$packedInUnitInfo = $requestProduct->packed_in.' / '.$requestProduct->unit_information;						
						$unitInfo = $requestProduct->unit_information;
						$TransportCompanyName=$requestProduct->transport_company_name;
						if($TransportCompanyName=='')
						{
							$TransportCompanyName='NA';
						}
						
						$VehicleContainerNo=$requestProduct->vehicle_container_no;
						if($VehicleContainerNo=='')
						{
							$VehicleContainerNo='NA';
						}
						
						$TcProductContent.= ' 
						<tr class="line_break" style="width: 100%;">
						<td  style="width: 46%;padding: 5px;border: 0.5px solid #000000;vertical-align:top;" colspan="2" >
						  <table style="border: none; ">
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Product No .:</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$prtCnt.'</td>
						  </tr>
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Order Number :</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$requestProduct->purchase_order_no.'</td>
						  </tr>
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Article No :</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$requestProduct->lot_ref_number.'</td>
						  </tr>
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Number of Units :</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$packedInUnitInfo.'</td>
						  </tr>
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Net Shipping Weight :</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.number_format($requestProduct->net_weight,2).' kg</td>
						  </tr>
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Supplementary Weight :</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$supplimentary_weight.'</td>
						  </tr>
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Certified Weight :</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.number_format($requestProduct->certified_weight,2).' kg</td>
						  </tr>
						  <tr style="border: none;">
						   <tr style="border: none;">
						  '.$production_date_print.'
						  </tr>
						  </tr>
						  </table>
						</td>

						<td  style="width: 60%;padding: 5px;border: 0.5px solid #000000" colspan="2" >
						  <table style="border: none;">						
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Product Category :</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$productname.' ('.$product_code.')</td>
						  </tr>
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Product Detail :</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$producttypename.' ('.$product_type_code.')</td>
						  </tr>
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Material Composition :</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$product_material_compostions_name.'</td>
						  </tr>
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">'.$product_label_grade_lbl.'</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$label_grade_name_with_standard.'</td>
						  </tr>
						  <tr style="border: none;">
						  <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Additional Info :</td>
						  <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$requestProduct->trade_name.'</td>
						  </tr>
						  <tr style="border: none;">
						  '.$LastProcessorDetails.'					
						  </tr>

						  <tr style="border: none;">

						  '.$LastProcessorDetailsLicense.'
						
						  </tr>
						  <tr style="border: none;">
						    '.$LastProcessorCompany.'
						  </tr>
						  </table>
						</td>
						</tr>
						';
						// $prdConsignee=$requestProduct->consignee;
						// $prdConsigneeCountry = ($prdConsignee->country?$prdConsignee->country->name:'');

						// $consigneeAddress='';
						// $consigneeName=$prdConsignee->name.'<br>';
						// $consigneeAddress1 = $prdConsignee->address.'<br>'.($prdConsignee->city ? $prdConsignee->city.',' : '').$prdConsignee->zipcode;
						// $consigneeAddress2 = ($prdConsignee->state ? $prdConsignee->state->name.', ' : '').''.$prdConsigneeCountry;
					
						// $TcShipmentContent.= '
						// <tr class="line_break" style="width: 100%;">
						// <td  style="width: 46%;padding: 5px;border: 0.5px solid #000000" colspan="2" >
						//   <table style="border: none;">
						//   <tr style="border: none;">
						//   <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Shipment No .:</td>
						//   <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$prtCnt.'</td>
						//   </tr>
						//   <tr style="border: none;">
						//   <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Shipment Date :</td>
						//   <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.date('Y-m-d',strtotime($requestProduct->transport_document_date)).'</td>
						//   </tr>
						//   <tr style="border: none;">
						//   <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Shipment Doc No :</td>
						//   <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$requestProduct->transport_document_no.'</td>
						//   </tr>
						//   <tr style="border: none;">
						//   <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Gross Shipping Weight :</td>
						//   <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.number_format($requestProduct->gross_weight,2).' kg</td>
						//   </tr>
						//   <tr style="border: none;">
						//   <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Invoice References :</td>
						//   <td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$requestProduct->invoice_no.' / '.date('Y-m-d',strtotime($requestProduct->invoice_date)).'</td>
						//   </tr>
						//   </table>
						// </td>
						
						// <td style="width: 60%; padding: 5px;border: 0.5px solid #000000"  colspan="2" >
						// <table style="border: none;"> 						
						// <tr style="border: none;">
						// <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Consignee Name and Address:</td>
						// <td style="width: 30%;border: none;" class="reportDetailLayoutInner"></td>
						// </tr>
						// <tr style="border: none;">
						// <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">'.$consigneeName.'</td>
						// </tr>
						// <tr style="border: none;">
						// <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">'.$consigneeAddress1.'</td>
						// </tr>
						// <tr style="border: none;">
						// <td  style="width: 40%;border: none;" class="reportDetailLayoutInner">'.$consigneeAddress2.'</td>
						// </tr>
						// <tr style="border: none;">
						// <td  style="width: 40%;border: none;" class="reportDetailLayoutInner"></td>
						// </tr>
						// </table>
						//  </td>						
						// </tr>
						// ';

						// $TCCertifiedRawMaterials.= '
						// <tr class="line_break" style="width: 100%;page-break-inside: avoid;">
						// 	<td  style="width: 40%;padding: 5px;border: 0.5px solid #000000;page-break-inside: avoid;" colspan="2" >
						// 		<table style="border: none;page-break-inside: avoid;">
						// 			<tr style="border: none;">
						// 				<td  style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$prtCnt.'.'.$RawMaterialName.' ('.$RawMaterialCode.') <br> Certified Weight :'.number_format($CertifiedWeight,2).' kg </td>
						// 			</tr>
						// 		</table>
						// 	<td>

						// 	<td  style="width:60%;padding: 5px;border: 0.5px solid #000000;page-break-inside: avoid;" colspan="2" >
						// 		<table style="border: none;page-break-inside: avoid;">
						// 			<tr style="border: none;">
						// 				<td  style="width: 37%;border: none;" class="reportDetailLayoutInner">'.$Country.''.$country_code_print.','.$State.' '.$state_code_print.'</td>
						// 			</tr>									
						// 		</table>
						// 	<td>

						// </tr>
						// ';
						
						$requestProdIds[] = $requestProduct->id;
						$prtCnt++;
					}	
				}	
				$rawCtn=1;
				if(count($arrRawMaterialIDs)>0)
				{
					$materialIds = array();
					$rawmaterialids = implode(',',$arrRawMaterialIDs);
					$requestProductids = implode(',',array_unique($requestProdIds));
					//echo $requestProductids;
					$connection = Yii::$app->getDb();	
					$connection->createCommand("SET SESSION group_concat_max_len = 1000000;")->execute();
					$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();
					$command = $connection->createCommand("SELECT sum(material_certified_weight) as certiW, tc_raw_material_id,material_id FROM tbl_tc_raw_material_used_weight_with_blended 
					where tc_request_product_id in (".$requestProductids.") group by material_id");
					$result = $command->queryAll();
					//echo count($result);
					if(count($result)>0)
					{
						foreach($result as $rm){
							$RawMaterialNameWithCode='NA';
							$Material = Material::find()->where(['id'=> $rm['material_id']])->one();
							if($Material!==null)
							{
								$RawMaterialNameWithCode = $Material['name'].'('.$Material['code'].')';
							}
							$rawmaterialidsbldarr=[];
							$rawmaterialidsbld='';
							$commandgeo = $connection->createCommand("SELECT * FROM tbl_tc_raw_material_used_weight_with_blended 
							where material_id = ".$rm['material_id']." AND tc_request_product_id in (".$requestProductids.") ");
							$resultgeo = $commandgeo->queryAll();
							//echo $rm['material_id'];
							//print_r($rammatusedweibld);
							if(count($resultgeo)>0){
								foreach($resultgeo as $bld)
								{
									$rawmaterialidsbldarr[] = $bld['tc_raw_material_id'];
								}
								//print_r($rawmaterialidsbldarr);
								$rawmaterialidsbld = implode(',',array_unique($rawmaterialidsbldarr));
							}
							// echo $rawmaterialidsbld; 
							$geo_location_print ='Geographic origin of raw materials not specified on incoming TC';
							$commandloc = $connection->createCommand("SELECT * FROM tbl_tc_raw_material_location_country_state where raw_material_id in (".$rawmaterialidsbld.") ");
							$resultloc = $commandloc->queryAll();
							 //echo count($resultloc);
							if(count($resultloc) >0)
							{
								$geo_location = array();
								foreach($resultloc as $RawMaterialObj)
								{
									$countrymod = Country::find()->where(['id'=>$RawMaterialObj['country_id']])->one();
									$statemod = State::find()->where(['id'=>$RawMaterialObj['state_id']])->one();
									$Country =($countrymod!==null)?$countrymod->name:"Geographic origin of raw materials not specified on incoming TC";
									$State =($statemod!==null)?$statemod->name:"";
									$CountryCode =($countrymod!==null)?$countrymod->code:"";
									$StateCode =($statemod!==null)?$statemod->code:"";

									// Logic for Display the Geo Codes, Country Code
									if($CountryCode == "" || $CountryCode == null){
										$country_code_print='<span></span>';
									}else {
										$country_code_print='<span>('.$CountryCode.')</span>';
									}
									//State Code
									if($StateCode == "" || $StateCode == null){
										$state_code_print='<span></span>';
									}else {
										$state_code_print='<span>('.$StateCode.')</span>';
									}

									
									if($State == "-NA-"){
										$geo_location[] = $Country.''.$country_code_print.' State - Not specified on incoming TC';
									}else {
										$geo_location[] = $Country.''.$country_code_print.'-'.$State.' '.$state_code_print;
									}
								}
								$geo_location_print=implode(',',array_unique($geo_location));
							}							
							$TCCertifiedRawMaterials.= '
							<tr class="line_break" style="width: 100%;page-break-inside: avoid;">
								<td  style="width: 40%;padding: 5px;border: 0.5px solid #000000;page-break-inside: avoid;" colspan="2" >
									<table style="border: none;page-break-inside: avoid;">
										<tr style="border: none;">
										<td  style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$rawCtn.'.'.$RawMaterialNameWithCode.' <br> Certified Weight :'.number_format($rm['certiW'],2).' kg </td>
										</tr>
									</table>
								<td>
								<td  style="width:60%;padding: 5px;border: 0.5px solid #000000;page-break-inside: avoid;" colspan="2" >
									<table style="border: none;page-break-inside: avoid;">
										<tr style="border: none;">
										<td  style="width: 37%;border: none;" class="reportDetailLayoutInner">'.$geo_location_print.'</td>
										</tr>									
									</table>
								<td>
	
							</tr>
							';
							$rawCtn++;
						}
					}		
				}

				$connection = Yii::$app->getDb();	
				$connection->createCommand("SET SESSION group_concat_max_len = 1000000;")->execute();
				$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();
				$command = $connection->createCommand("SELECT id,sum(gross_weight) as gross_weight,GROUP_CONCAT( DISTINCT invoice_no,'/', invoice_date SEPARATOR ',<br>') as gro_invoice_number,transport_document_date,transport_document_no,consignee_id
				FROM tbl_tc_request_product 
				where id in (".$requestProductids.") group by transport_document_no  ORDER BY id ASC");
				$result = $command->queryAll();
				$shipmentCtn=1;
				if(count($result)>0)
				{
					foreach($result as  $val)
					{
						$consignee = Buyer::find()->where(['id'=> $val['consignee_id']])->one();
						if($consignee != null)
						{
							//$consignee=$requestProduct->consignee;
							$prdConsigneeCountry = ($consignee->country?$consignee->country->name:'');
							$consigneeName=$consignee->name.'<br>';
							$consigneeAddress1 = $consignee->address.'<br>'.($consignee->city ? $consignee->city.',' : '').$consignee->zipcode;
							$consigneeAddress2 = ($consignee->state ? $consignee->state->name.', ' : '').''.$prdConsigneeCountry;
						}

						$TcShipmentContent.= '
							<tr class="line_break" style="width: 100%;">
							<td  style="width: 46%;padding: 5px;border: 0.5px solid #000000" colspan="2" >
							<table style="border: none;">
						  		<tr style="border: none;">
						  		<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Shipment No .:</td>
						  		<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$shipmentCtn.'</td>
						  		</tr>
						  		<tr style="border: none;">
						  		<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Shipment Date :</td>
						  		<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.date('Y-m-d',strtotime($val['transport_document_date'])).'</td>
						  		</tr>
						 		<tr style="border: none;">
						  		<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Shipment Doc No :</td>
						 		<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$val['transport_document_no'].'</td>
						  		</tr>
						  		<tr style="border: none;">
						  		<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Gross Shipping Weight :</td>
						  		<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.number_format($val['gross_weight'],2).' kg</td>
						  		</tr>
						  		<tr style="border: none;">
						  		<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Invoice References :</td>
						 		<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.$val['gro_invoice_number'].'</td>
						  		</tr>
						 		</table>
								</td>
								'.$requestProduct->invoice_no.' / '.date('Y-m-d',strtotime($requestProduct->invoice_date)).'
								<td style="width: 60%; padding: 5px;border: 0.5px solid #000000"  colspan="2" >
								<table style="border: none;"> 						
								<tr style="border: none;">
								<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Consignee Name and Address:</td>
								<td style="width: 30%;border: none;" class="reportDetailLayoutInner"></td>
								</tr>
								<tr style="border: none;">
								<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">'.$consigneeName.'</td>
								</tr>
								<tr style="border: none;">
								<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">'.$consigneeAddress1.'</td>
								</tr>
								<tr style="border: none;">
								<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">'.$consigneeAddress2.'</td>
								</tr>
								<tr style="border: none;">
								<td  style="width: 40%;border: none;" class="reportDetailLayoutInner"></td>
								</tr>
								</table>
								 </td>						
								</tr>
						';
						$shipmentCtn++;
					}
				}
				
				$TcProductContent.= '</table>';
				$TcShipmentContent.= '</table>';
				$TCCertifiedRawMaterials.= '</table>';
			// -------------- TC Product Code End Here ------------------------
		
 			$TcCertifiedWeight='';
		    $TcCertifiedWeight='<div class="standardcertifiedweight"></div>';
            $SplitCertifiedWeight = array();
			$SplitStandardName = array();
			$varStandardWeight='';
			$varStandardName='';
			$valweight = array();

	    //    if(count($model->standard)>0 && count($model->standard)>1){

				
		// 		    $prtSta=1;
		// 			$standardweightic=0;
		// 			foreach($model->standard as $reqstandard){

		// 				$connection = Yii::$app->getDb();	
		// 				$connection->createCommand("SET SESSION group_concat_max_len = 1000000;")->execute();
		// 				$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();							
					
		// 				$command = $connection->createCommand("
		// 				SELECT tc_request_id, 
		// 				SUM(std_1_certified_weight) as std_1_certified_weight, 
		// 				SUM(std_2_certified_weight) as std_2_certified_weight
		// 				FROM tbl_tc_request_product 
		// 				where tc_request_id=".$requestID." 
		// 				GROUP BY tc_request_id");

		// 				$result = $command->queryOne();

		// 				if(count($result )>0){
		// 					$SplitCertifiedWeight[]= $result ['std_1_certified_weight'];
		// 					$SplitCertifiedWeight[]= $result ['std_2_certified_weight'];
		// 				}
			
		// 				//$SplitCertifiedWeight[]= $result['certified_weight'];

		// 				$SplitStandardName[]= $reqstandard->standard->name;

       	// 				$varStandardName=implode(" - ",$SplitStandardName);	
		// 				$varStandardWeight=implode(" - ",$SplitCertifiedWeight);
						

		// 				$TcCertifiedWeight.= '
		// 				    <span style="font-weight:bold;">'.$prtSta.' . </span>
		// 					<span>'.$varStandardName.' - </span>
		// 					<span>'.$SplitCertifiedWeight[$standardweightic].'</span><br>
		// 				';

		// 			$prtSta++;
		// 			$standardweightic++;
		// 			unset($SplitStandardName);
		// 			unset($SplitCertifiedWeight);
						
		// 			}					
		// 	}else if(count($model->standard) == 1){
		// 		$TcCertifiedWeight.= '<span> &nbsp; &nbsp; &nbsp;'.number_format($total_certified_weight,2).' Kg</span>';
		// 	}
		$TcCertifiedWeight.= '<span> &nbsp; &nbsp; &nbsp;'.number_format($total_certified_weight,2).' Kg</span>';
			// -------------- Standard Based Certified Weight End Here ------------------------
			$standards_logos='
					<div style="float:left;width:100%;'.$logoStyle.'">';
					if(count($arrTcLogo) == 1)
					{
						if(is_array($arrTcLogo) && count($arrTcLogo)>0){
						foreach($arrTcLogo as $certLogoKey => $certLogo){
							$logoWidth='width:115px;';
							if(is_array($tc_std_code_array) && isset($tc_std_code_array[$certLogoKey]) && $tc_std_code_array[$certLogoKey]=='GRS')
							{
								$logoWidth='width:190px;';
							}
							$standards_logos.='<img style="'.$logoWidth.'{PADDINGLOGOTOP}padding-left:5px;" src="'.Yii::$app->params['image_files'].''.$certLogo.'" border="0">';
						}
					}	
					} else {
						if(is_array($arrTcLogo) && count($arrTcLogo)>0){
						foreach($arrTcLogo as $certLogoKey => $certLogo)
						{
							$logoWidth='width:110px;';
							
							if(is_array($tc_std_code_array) && isset($tc_std_code_array[$certLogoKey]) && $tc_std_code_array[$certLogoKey]=='GRS')
							{
								$logoWidth='width:100px;';
								//echo $logoWidth;
							}
							$standards_logos.='<img style="'.$logoWidth.'{PADDINGLOGOTOP}padding-left:5px;" src="'.Yii::$app->params['image_files'].''.$certLogo.'" border="0">';
						}
					  }	
					}
					$standards_logos.='</div>';
					$html.='
					<htmlpagefooter name="htmlpagefooter">
															
					<table cellpadding="0" cellspacing="0" border="0" width="100%" style="border:none;">					
					<tr>
						<td style="text-align:left;width:34%;" valign="middle" class="reportDetailLayoutInnerWithoutBorder">
							 '.$DatePlaceContent.'
							<img style="width:120px;" src="'.Yii::$app->params['image_files'].'certificate-sign.png" border="0">
							<br>
							<p>Mahmut Sogukpinar</p>
						</td>
						<td style="text-align:center;width:28%;" valign="middle" class="reportDetailLayoutInnerWithoutBorder">
							<div style="padding-top:5px;">Certification Body</div>
							<div style="float:left;width:100%;"><img style="width:110px;{PADDINGTOP}" src="'.Yii::$app->params['image_files'].'gcl-stamp.png" border="0"></div>
						</td>
						<td style="text-align:center;width:38%;padding-bottom:5px;"  class="reportDetailLayoutInnerWithoutBorder">
						<div style="padding-top:5px;padding-bottom:5px;float:left;width:100%;">Standard Logo</div>
							'.$standards_logos.'
						</td>
					<tr>
				 </table>	
	
				 <span style="color:#000000;font-size:10px;font-family:Arial;padding-bottom:3px;text-align:left;">
					'.$GotsLabel.'</span>
					<div style="color:#000000;font-size:10px;font-family:Arial;padding-bottom:3px;text-align:left;">
						This electronically issued document is the valid original version. <br>	To confirm this certificate, please scan the QR code located on the top right corner. The domain you see should be : <a style="color:black;" href="https://ssl.gcl-intl.com">https://ssl.gcl-intl.com</a>, </div>
					<div style="color:#000000;font-size:10px;font-family:Arial;text-align:left;"> Seller License Number <span style="font-weight:bold;">GCL-'.$customeroffernumber.'</span></div>
					<div style="color:#000000;font-size:10px;font-family:Arial;text-align:right;">Page {PAGENO} of {nbpg}</div>
				
				</htmlpagefooter>
				';			

				$html.= '
				<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayoutInner" style="margin-top:15px;">
	
					<tr>
						<td class="reportDetailLayoutInner" width="49%" style="padding: 10px;">
							<span class="innerTitleMain">1. Certification Body</span> <br><br>
	
							GCL International Ltd<br>Level 1, Devonshire House, One Mayfair Place, <br> London, W1J 8AJ, <br> United Kingdom.<br><br>
	
							<span class="innerTitle">Licensing Code of Certification Body :</span> 
							'.$tc_std_licence_number.'
						</td>
	
						<td class="reportDetailLayoutInner" width="51%" style="padding: 10px;">
							<span class="innerTitleMain">2.'.$sender_label.' of Certified Products</span><br><br>
							'.$applicationCompanyName.'<br>
							'.$applicationCompanyAddress.'<br>
							'.$applicationCompanyAddressTownPostcode.'<br>
							'.$applicationCompanyAddressStateCountry.'<br><br>
							<span class="innerTitle">SC Number:  '.$tc_scope_licence_number.'</span><br>
							<span class="innerTitle">License No.:  </span>  GCL-'.$customeroffernumber.'
								
						</td>
					</tr>				
	
					<tr>
						<td class="reportDetailLayoutInner" rowspan="3" style="padding: 10px;">
							<span class="innerTitleMain">3.'.$reciever_label.' of Certified Products</span> <br>
							 <br>
							'.$buyer->name.'<br>
							'.$buyer->address.' <br> '.$buyer->city.','.$buyer->zipcode.' <br> '.($buyer->state?$buyer->state->name.', ':'').($buyer->country?$buyer->country->name:'').' <br><br>
							<span class="innerTitle">License No.:  </span>'.($buyer->client_number ? $buyer->client_number : '-').'
						</td>
	
						<td class="reportDetailLayoutInner" style=" padding-bottom: 15px;padding: 10px;">
							<span class="innerTitleMain">4. Gross shipping weight </span> <br> &nbsp; &nbsp; &nbsp;'.number_format($total_gross_weight,2).' kg
						</td>
						
					</tr>
	
					<tr>
						<td class="reportDetailLayoutInner" style=" padding-bottom: 15px;padding: 10px;">
							<span class="innerTitleMain">5. Net shipping weight</span> <br> &nbsp; &nbsp; &nbsp;'.number_format($total_net_weight,2).' kg
						</td>
					</tr>
	
					<tr>
						<td class="reportDetailLayoutInner" style=" padding-bottom: 15px;padding: 10px;">
							<span class="innerTitleMain">6. Certified weight </span> <br>'.$TcCertifiedWeight.'
						</td>	
					</tr>
					
					<tr>
						<td class="reportDetailLayoutInner" style="padding: 10px;" colspan="2">
							<span class="innerTitleMain">7. Declarations by Certification Body</span> <br><br>
							<div>
							<span style="padding: 15px;">'.$declaration.'</span></div>
							</td>
					</tr>';
					
					if($tc_std_code == "GOTS" ) {
						$html.= '<tr>
						<td class="reportDetailLayoutInner"   style="padding: 10px;" colspan="2">
						   <span class="innerTitleMain"></span>';
						   $html .= $additional_declaration;
						   $html.='</td>
						</tr>';
					}
					
	
					if( $tc_std_code =="OCS" ||  $tc_std_code =="GRS,OCS"  ||  $tc_std_code =="OCS,GRS" ||  $tc_std_code =="OCS,RCS" ||  $tc_std_code =="RCS,OCS" ) {
						$html.= '<tr>
						<td class="reportDetailLayoutInner"   style="padding: 10px;" colspan="2">
						   <span class="innerTitleMain"></span>
							'.$additional_declaration.' <br> <br> <b>Additionally, certification of the organic material used for the products listed complies with:</b>  '.$ocs_additional_decalartion.'
						   </td>
						</tr>';
					}
					
					if($tc_std_code == "CCS"){
						$html.= '<tr>
						<td class="reportDetailLayoutInner"   style="padding: 10px;" colspan="2">
						<span class="innerTitleMain"></span>
						Certification of products included on this transaction certificate was done in accordance with the Content Claim Standard (CCS), which is owned by Textile 
						Exchange.
						</tr>';
					}
					
					$html.= '<tr>
					<td class="reportDetailLayoutInner"   style="padding: 10px;" colspan="2">
					<span class="innerTitleMain"></span>
					'.$standard_declaration.'
					</td>
					</tr>';
	
					$html.= '<tr >
					<td class="reportDetailLayoutInner"   style=" padding-bottom: 15px;padding: 10px;" colspan="2">
					<span class="innerTitleMain">8. Certified Input References</span><br><br>
	
					<table  cellspacing="0" cellpadding="0" style="border: none;">
	
						<tr style="border: none;">
						<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Input TCs:</td>
						<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.($raw_material_tc_no ? $raw_material_tc_no : '-').' </td>
						</tr>
	
						<tr style="border: none;">
						<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Farm SCs :</td>
						<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.($raw_material_farm_sc_no ? $raw_material_farm_sc_no : '-').'</td>
						</tr>
	
						<tr style="border: none;">
						<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Farm TCs: </td>
						<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.($raw_material_farm_tc_no ? $raw_material_farm_tc_no : '-').'</td>
						</tr>
	
						<tr style="border: none;">
						<td  style="width: 40%;border: none;" class="reportDetailLayoutInner">Trader TCs for Organic Material:</td>
						<td style="width: 30%;border: none;" class="reportDetailLayoutInner">'.($raw_material_trader_tc_no ? $raw_material_trader_tc_no : '-').'</td>
						</tr>
	
					</table>
					
					</td>
					</tr>
					
								
				</table>';			
									
				
				//  $html.= '<pagebreak />										
				//  <div style="font-family:Arial;padding-top:10px;font-size:14px;font-weight:bold;text-align: center;border:none;"> Transaction Certificate Number '.$TransactionCertificateNo.' (continued) 
				//  <br><div style="font-family:Arial;padding-top:2px;font-size:14px;font-weight:regular!important;">'.$tc_header_standard_title.'</div></div>';
	
				$html.= '<pagebreak />	
					<div  class="reportDetailLayoutInner" style="padding-top:10px;page-break-inside: avoid;">
						<span class="innerTitleMain">9. Shipments</span>
						<div style="padding-top:10px;"> 
						'.$TcShipmentContent.'	</div>
					</div>
					<div class="reportDetailLayoutInner" style="padding-top:10px;">
						<span class="innerTitleMain">10. Certified Products</span>
						<div style="padding-top:10px;"> 
						'.$TcProductContent.'	</div>
					</div>
				';
	
				
				$html.= '
				<div class="reportDetailLayoutInner" style="padding-top:10px;page-break-inside: avoid;">
					<span class="innerTitleMain">11. Certified Raw Materials and Declared Geographic Origin</span> <br>
					<div style="padding-top:10px;"> 
					'.$TCCertifiedRawMaterials.'
					</div>
				</div>';	
	
				
				$html.= '
				<div class="reportDetailLayoutInner" style="padding: 3px;page-break-inside: avoid;">
				<div style="border:1px solid #000000;padding: 10px;">
				<span class="innerTitleMain">12. Declarations by Seller of Certified Products</span><br><br>
				<span class="innerTitle">The certified product(s) covered in this certificate have been outsourced to a subcontractor:</span> 
					<b>'.($Sub_contractor_declaration).'</b>
					 <br><br>
					<span class="innerTitle"></span> 
					'.($comments).' <br>
				</div>
				</div>';			
				//$pdfName = 'TRANSACTION_CERTIFICATE_' . date('YmdHis') . '.pdf';
				$pdfName = 'TRANSACTION_CERTIFICATE_'.$customeroffernumber.'_'.$TransactionCertificateNo.'.pdf';
				$filepath=Yii::$app->params['tc_files']."tc/".$pdfName;
				$mpdf->SetProtection(array('copy','print'), '', 'PeriyaRagasiyam');			
				$mpdf->WriteHTML($html);	
			
			if($returnType)
			{
				$mpdf->Output($filepath,'F');												
				$model->filename=$pdfName;
				$model->save();				
			}else{
				$mpdf->Output($filepath,'D');	
			}
			
		}
		// ----------------------- Generate TC Code End Here ----------------------------
	}

	public function actionAssignReviewer()
	{
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		$data  = Yii::$app->request->post();
		$userData = Yii::$app->userdata->getData();
		$userid = $userData['userid'];
		if($data)
		{
			if(!Yii::$app->userrole->hasRights(array('application_review')) && $userData['user_type'] != 1)
			{
				return false;
			}
			
			$RequestModel = new Request();
			$Request = Request::find()->where(['id' => $data['id'],'status'=> $RequestModel->arrEnumStatus['waiting_for_review']])->one();
			if($Request!==null)
			{
				$reviewermodel = new RequestReviewer();
				$reviewermodel->tc_request_id = $data['id'];
				$reviewermodel->user_id = $userid;
				$reviewermodel->created_by = $userid;
				if($reviewermodel->validate() && $reviewermodel->save())
				{
					
					$Request->status = $Request->arrEnumStatus['review_in_process'];
					$Request->save();
					$responsedata=array('status'=>1,'message'=>"Assigned Successfully!",'request_status'=>$Request->status);
					

				}
			}
			
		}
		return $responsedata;
	}
	
	public function actionEvidenceDocument(){
		$datapost = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($datapost) 
		{	
			$userData = Yii::$app->userdata->getData();
			$userid=$userData['userid'];
			$user_type=$userData['user_type'];
			$role=$userData['role'];
			$rules=$userData['rules'];
			$resource_access=$userData['resource_access'];
			$is_headquarters =$userData['is_headquarters'];
			$franchiseid=$userData['franchiseid'];
			$role_chkid=$userData['role_chkid'];

			$data = json_decode($datapost['formvalues'],true);
			if($data)
			{
				$RequestModel = Request::find()->where(['id'=>$data['id']])->one();
				if($RequestModel !== null)
				{
					if(!Yii::$app->userrole->isValidApplication($RequestModel->app_id))
					{
						$responsedata=array('status'=>0,'message'=>'Application is not valid.');
						return $responsedata;
					}
					
					$showedit= $this->canEditTc($RequestModel);
					if($showedit==0){
						return $responsedata;
					}
					
					
					//return $data; die;
					$target_dir = Yii::$app->params['tc_files']."evidence_files/"; 

					$editStatus=1;
					//RequestEvidence::find()->where(['tc_request_id'=>$data['id'] ]);
					RequestEvidence::deleteAll(['tc_request_id' => $data['id']]);

					$findocs = $data['finacial_documents'];
					if($data['sel_finacial_evidence']==2 && count($findocs)>0){
						foreach($findocs as $fdoc){
							    $filename = $fdoc['name'];
								if($filename!='')
								{
									Yii::$app->globalfuns->removeFiles($filename,$target_dir);							
								}
						}
					}
					/*
					$RequestEvidence = RequestEvidence::find()->where(['tc_request_id'=>$data['id'] ])->one();
					if($RequestEvidence===null)
					{

						$RequestEvidence = new RequestEvidence();
						$editStatus=0;
						$RequestEvidence->created_by = $userid;
						$RequestEvidence->tc_request_id = $data['id'];
					}else{
						$RequestEvidence->updated_by = $userid;
					}
					*/
					
					$sales_invoice_with_packing_list = $data['sales_invoice_with_packing_list'];
					if(count($sales_invoice_with_packing_list)>0){
						$icnt = 0;
						foreach($sales_invoice_with_packing_list as $filedetails){
							if($filedetails['deleted'] != '1'){
								$filename= '';
								if($filedetails['added'] == '1'){
									if(isset($_FILES['sales_invoice_with_packing_list']['name'][$icnt]))
									{
										$tmp_name = $_FILES["sales_invoice_with_packing_list"]["tmp_name"][$icnt];
										$name = $_FILES["sales_invoice_with_packing_list"]["name"][$icnt];
										$filename=Yii::$app->globalfuns->postFiles($name,$tmp_name,$target_dir);	
																	
									}
								}else{
									$filename = $filedetails['name'];
								}
								
								$RequestEvidence = new RequestEvidence();
								$RequestEvidence->evidence_file = $filename;
								$RequestEvidence->tc_request_id = $data['id'];
								$RequestEvidence->evidence_type = 'sales_invoice';
								$RequestEvidence->save();
								
							}else{
								$filename = $filedetails['name'];
								if($filename!='')
								{
									Yii::$app->globalfuns->removeFiles($filename,$target_dir);							
								}
							}
							$icnt++;
						}
					}
					
					
					$product_handled_by_subcontractor = $data['product_handled_by_subcontractor'];
					if(count($product_handled_by_subcontractor)>0){
						$icnt = 0;
						foreach($product_handled_by_subcontractor as $filedetails){
							if($filedetails['deleted'] != '1'){
								$filename= '';
								if($filedetails['added'] == '1'){
									if(isset($_FILES['product_handled_by_subcontractor']['name'][$icnt]))
									{
										$tmp_name = $_FILES["product_handled_by_subcontractor"]["tmp_name"][$icnt];
										$name = $_FILES["product_handled_by_subcontractor"]["name"][$icnt];
										$filename=Yii::$app->globalfuns->postFiles($name,$tmp_name,$target_dir);	
																	
									}
								}else{
									$filename = $filedetails['name'];
								}
								
								$RequestEvidence = new RequestEvidence();
								$RequestEvidence->evidence_file = $filename;
								$RequestEvidence->tc_request_id = $data['id'];
								$RequestEvidence->evidence_type = 'product_handled_by_subcontractor';
								$RequestEvidence->sel_product_evidence = $data['sel_product_evidence'];

								$RequestEvidence->save();
								
							}else{
								$filename = $filedetails['name'];
								if($filename!='')
								{
									Yii::$app->globalfuns->removeFiles($filename,$target_dir);							
								}
							}
							$icnt++;
						}
					}




					$transport_document = $data['transport_document'];
					if(count($transport_document)>0){
						$icnt = 0;
						foreach($transport_document as $filedetails){
							if($filedetails['deleted'] != '1'){
								$filename= '';
								if($filedetails['added'] == '1'){
									if(isset($_FILES['transport_document']['name'][$icnt]))
									{
										$tmp_name = $_FILES["transport_document"]["tmp_name"][$icnt];
										$name = $_FILES["transport_document"]["name"][$icnt];
										$filename=Yii::$app->globalfuns->postFiles($name,$tmp_name,$target_dir);	
																	
									}
								}else{
									$filename = $filedetails['name'];
								}
								
								$RequestEvidence = new RequestEvidence();
								$RequestEvidence->evidence_file = $filename;
								$RequestEvidence->tc_request_id = $data['id'];
								$RequestEvidence->evidence_type = 'transport_document';
								$RequestEvidence->save();
							}else{
								$filename = $filedetails['name'];
								if($filename!='')
								{
									Yii::$app->globalfuns->removeFiles($filename,$target_dir);							
								}
							}
							$icnt++;
						}
					}

					$mass_balance_sheet = $data['mass_balance_sheet'];
					if(count($mass_balance_sheet)>0){
						$icnt = 0;
						foreach($mass_balance_sheet as $filedetails){
							if($filedetails['deleted'] != '1'){
								$filename= '';
								if($filedetails['added'] == '1'){
									if(isset($_FILES['mass_balance_sheet']['name'][$icnt]))
									{
										$tmp_name = $_FILES["mass_balance_sheet"]["tmp_name"][$icnt];
										$name = $_FILES["mass_balance_sheet"]["name"][$icnt];
										$filename=Yii::$app->globalfuns->postFiles($name,$tmp_name,$target_dir);	
																	
									}
								}else{
									$filename = $filedetails['name'];
								}
								
								$RequestEvidence = new RequestEvidence();
								$RequestEvidence->evidence_file = $filename;
								$RequestEvidence->tc_request_id = $data['id'];
								$RequestEvidence->evidence_type = 'mass_balance';
								$RequestEvidence->save();
							}else{
								$filename = $filedetails['name'];
								if($filename!='')
								{
									Yii::$app->globalfuns->removeFiles($filename,$target_dir);							
								}
							}
							$icnt++;
						}
					}

					$test_report = $data['test_report'];
					if(count($test_report)>0){
						$icnt = 0;
						foreach($test_report as $filedetails){
							if($filedetails['deleted'] != '1'){
								$filename= '';
								if($filedetails['added'] == '1'){
									if(isset($_FILES['test_report']['name'][$icnt]))
									{
										$tmp_name = $_FILES["test_report"]["tmp_name"][$icnt];
										$name = $_FILES["test_report"]["name"][$icnt];
										$filename=Yii::$app->globalfuns->postFiles($name,$tmp_name,$target_dir);	
																	
									}
								}else{
									$filename = $filedetails['name'];
								}
								
								$RequestEvidence = new RequestEvidence();
								$RequestEvidence->evidence_file = $filename;
								$RequestEvidence->tc_request_id = $data['id'];
								$RequestEvidence->evidence_type = 'test_report';
								$RequestEvidence->save();
							}else{
								$filename = $filedetails['name'];
								if($filename!='')
								{
									Yii::$app->globalfuns->removeFiles($filename,$target_dir);							
								}
							}
							$icnt++;
						}
					}
					$finacial_documents = $data['finacial_documents'];
					if($data['sel_finacial_evidence']==1 && count($finacial_documents)>0){
						$icnt = 0;
						foreach($finacial_documents as $filedetails){
							if($filedetails['deleted'] != '1'){
								$filename= '';
								if($filedetails['added'] == '1'){
									if(isset($_FILES['finacial_documents']['name'][$icnt]))
									{
										$tmp_name = $_FILES["finacial_documents"]["tmp_name"][$icnt];
										$name = $_FILES["finacial_documents"]["name"][$icnt];
										$filename=Yii::$app->globalfuns->postFiles($name,$tmp_name,$target_dir);	
																	
									}
								}else{
									$filename = $filedetails['name'];
								}
								
								$RequestEvidence = new RequestEvidence();
								$RequestEvidence->evidence_file = $filename;
								$RequestEvidence->tc_request_id = $data['id'];
								$RequestEvidence->evidence_type = 'finacial_documents';
								$RequestEvidence->save();
							}else{
								$filename = $filedetails['name'];
								if($filename!='')
								{
									Yii::$app->globalfuns->removeFiles($filename,$target_dir);							
								}
							}
							$icnt++;
						}
					}
					$reqmod = Request::find()->where(['id'=>$data['id'] ])->one();
					if($reqmod!==null)
					{
						$reqmod->finacial_evidence_consent = $data['sel_finacial_evidence'];
						$reqmod->finacial_doc_reason = $data['finacial_doc_reason'];
						$reqmod->save();
					}

					if($data['savetype'] == 'approval'){
						$Request = Request::find()->where(['id'=>$data['id'] ])->one();
						if($Request!==null)
						{
							$Request->status = $Request->arrEnumStatus['waiting_for_osp_review'];
							$Request->submit_to_oss_at = date('Y-m-d');
							$Request->save();
						}
					}else{
						$Request = Request::find()->where(['id'=>$data['id'] ])->one();
						if($Request!==null && $Request->status== $Request->arrEnumStatus['open'] )
						{
							if($data['savetype'] != 'other'){
								$Request->status = $Request->arrEnumStatus['draft'];
								$Request->save();
							}
						}

					}			
					$responsedata=array('status'=>1,'message'=>$editStatus?"Updated Successfully!":"Created Successfully");
				}
			}		
		}
		return $responsedata;
	}

	public function actionDownload()
	{

				$data = Yii::$app->request->post();
                $filename = $data['filename'];
				header('Access-Control-Allow-Origin: *');
				header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
				header('Access-Control-Max-Age: 1000');
				header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
				
				$filepath=Yii::$app->params['tc_files']."evidence_form/".$filename;
				if(file_exists($filepath)) 
				{
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
					header('Access-Control-Expose-Headers: Content-Length,Content-Disposition,filename,Content-Type;');
					header('Access-Control-Allow-Headers: Content-Length,Content-Disposition,filename,Content-Type');
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					header('Content-Length: ' . filesize($filepath));
					flush(); // Flush system output buffer
					readfile($filepath);
				}
				die;	
		}
	public function restoreRawMaterialWeight($tc_request_id)
	{
		$TcRequestProductObj = new RequestProduct();		
		$TcRequestProductModel = RequestProduct::find()->where(['tc_request_id'=>$tc_request_id])->all();		
		if(count($TcRequestProductModel)>0)
		{

			foreach($TcRequestProductModel as $requestedProduct)
			{
				$requestedProductID = $requestedProduct->id;
				$requestproductinputmaterialObj= $requestedProduct->requestproductinputmaterial;				
				if(count($requestproductinputmaterialObj)>0)
				{
					foreach($requestproductinputmaterialObj as $rpinputmaterial)
					{
						$RawMaterialUpdate = RawMaterial::find()->where(['id'=>$rpinputmaterial->tc_raw_material_id])->one();
						if($RawMaterialUpdate !== null)
						{
							/*
							if($RawMaterialUpdate->is_certified=="1"){
								$certified_weight = $RawMaterialUpdate->certified_weight;
								$total_weight = $certified_weight + $rpinputmaterial->used_weight;
								$RawMaterialUpdate->certified_weight = $total_weight;
							}else if($RawMaterialUpdate->is_certified=="2"){
								$net_weight = $RawMaterialUpdate->net_weight;
								$total_weight = $net_weight + $rpinputmaterial->used_weight;
								$RawMaterialUpdate->net_weight = $total_weight;
							}
							*/
							
							$net_weight = $RawMaterialUpdate->net_weight;
							$total_weight = $net_weight + $rpinputmaterial->used_weight;
							$RawMaterialUpdate->net_weight = $total_weight;
							$RawMaterialUpdate->total_used_weight  = $RawMaterialUpdate->total_used_weight - $rpinputmaterial->used_weight;
							$RawMaterialUpdate->save();
						}
						$RawMaterialProductUpdate = RawMaterialProduct::find()->where(['id'=>$rpinputmaterial->tc_raw_material_product_id])->one();
						if($RawMaterialProductUpdate !== null)
						{
							$net_weight = $RawMaterialProductUpdate->net_weight;
							$total_weight = $net_weight + $rpinputmaterial->used_weight;
							$RawMaterialProductUpdate->net_weight = $total_weight;
							$RawMaterialProductUpdate->total_used_weight = $RawMaterialProductUpdate->total_used_weight - $rpinputmaterial->used_weight;
							$RawMaterialProductUpdate->save();
						}
					}
				}
				
				// Change the Status to Used Weight Related Entries in Raw Material				
				TcRawMaterialUsedWeight::updateAll(['status' => 2], ['tc_request_product_id' => $requestedProductID]);
				
			}
		}	
	}
	
	// Action WithDraw and revert tc
	public function actionRevert()
	{

		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$data = Yii::$app->request->post();

		// Update the rejected status
		$Request = Request::find()->where(['id' => $data['id']])->one();
		if($Request!==null)
		{
			$Request->sel_withdraw = isset($data['sel_withdraw'])?$data['sel_withdraw']:'';
			$Request->wcomment = isset($data['wcomment'])?$data['wcomment']:'';
			$Request->status = $Request->arrEnumStatus['withdrawn'];
			if($Request->save()){
				$responsedata=array('status'=>1,'message'=>'Withdrawn has been updated successfully');	
			}
		}


		// Restore the Raw MaterialWeight
		$TcRequestProductObj = new RequestProduct();		
		$TcRequestProductModel = RequestProduct::find()->where(['tc_request_id'=>$data['id']])->all();		
		if(count($TcRequestProductModel)>0)
		{
			foreach($TcRequestProductModel as $requestedProduct)
			{
				$requestedProductID = $requestedProduct->id;
				$requestproductinputmaterialObj= $requestedProduct->requestproductinputmaterial;				
				if(count($requestproductinputmaterialObj)>0)
				{
					foreach($requestproductinputmaterialObj as $rpinputmaterial)
					{
						$RawMaterialUpdate = RawMaterial::find()->where(['id'=>$rpinputmaterial->tc_raw_material_id])->one();
						if($RawMaterialUpdate !== null)
					{
						$net_weight = $RawMaterialUpdate->net_weight;
						$total_weight = $net_weight + $rpinputmaterial->used_weight;
						$RawMaterialUpdate->net_weight = $total_weight;
						$RawMaterialUpdate->total_used_weight  = $RawMaterialUpdate->total_used_weight - $rpinputmaterial->used_weight;
						$RawMaterialUpdate->save();
					}
						$RawMaterialProductUpdate = RawMaterialProduct::find()->where(['id'=>$rpinputmaterial->tc_raw_material_product_id])->one();
						if($RawMaterialProductUpdate !== null)
						{
						$net_weight = $RawMaterialProductUpdate->net_weight;
						$total_weight = $net_weight + $rpinputmaterial->used_weight;
						$RawMaterialProductUpdate->net_weight = $total_weight;
						$RawMaterialProductUpdate->total_used_weight = $RawMaterialProductUpdate->total_used_weight - $rpinputmaterial->used_weight;
						$RawMaterialProductUpdate->save();
						}
				}
			}		
			// Change the Status to Used Weight Related Entries in Raw Material				
			TcRawMaterialUsedWeight::updateAll(['status' => 2], ['tc_request_product_id' => $requestedProductID]);
		}
	 }
   }
	
	public function updateProductWeightToRequest($tc_request_id)
	{
		$RequestModel = new Request();
		if($tc_request_id!='')
		{
			$total_gross_weight=0;
			$total_net_weight=0;
			$total_certified_weight=0;
			$total_wastage_weight=0;
			$grand_total_net_weight=0;
			$grand_total_used_weight=0;
			$requestedProductValidStatus=1;
			
			$Request = Request::find()->where(['id'=>$tc_request_id ])->one();
			if($Request!==null)
			{					
				$TcRequestProductModel = $Request->product;
				$TcRequestProductModelCount = count($TcRequestProductModel);
				if($TcRequestProductModelCount>0)
				{
					foreach($TcRequestProductModel as $requestedProduct)
					{
						$total_gross_weight=$total_gross_weight+$requestedProduct->gross_weight;
						$total_net_weight=$total_net_weight+$requestedProduct->net_weight;
						$total_certified_weight=$total_certified_weight+$requestedProduct->certified_weight;
						$total_wastage_weight=$total_wastage_weight+$requestedProduct->wastage_weight;
						$grand_total_net_weight=$grand_total_net_weight+$requestedProduct->total_net_weight;
						$grand_total_used_weight=$grand_total_used_weight+$requestedProduct->total_used_weight;
						if($requestedProduct->status==0)
						{
							$requestedProductValidStatus=0;
						}
					}
				}
				
				$Request->total_gross_weight=$total_gross_weight;
				$Request->total_net_weight=$total_net_weight;
				$Request->total_certified_weight=$total_certified_weight;
				$Request->total_wastage_weight=$total_wastage_weight;
				$Request->grand_total_net_weight=$grand_total_net_weight;
				$Request->grand_total_used_weight=$grand_total_used_weight;				
				
				$overall_input_status=0;
				if($requestedProductValidStatus==0){
					$overall_input_status = $RequestModel->arrEnumOverallInputStatus['open'];				
				}else{
					$overall_input_status = $RequestModel->arrEnumOverallInputStatus['input_added'];
				}
				$Request->overall_input_status = $overall_input_status;
				
				$Request->save();
			}	
		}	
	}

	public function actionChangeProductWastagePercentage(){
		//wastage_percentage
		//wastage_percentage

		//certified_weight

		// wastage_weight -- total_net_weight
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$data = yii::$app->request->post();		
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		if($data)
		{
			$userData = Yii::$app->userdata->getData();
			$userid=$userData['userid'];
			$user_type=$userData['user_type'];
			$role=$userData['role'];
			$rules=$userData['rules'];
			$resource_access=$userData['resource_access'];
			$is_headquarters =$userData['is_headquarters'];
			$franchiseid=$userData['franchiseid'];
			$role_chkid=$userData['role_chkid'];

			$RequestProduct = RequestProduct::find()->where(['id'=>$data['tc_request_product_id'] ])->one();
			if($RequestProduct !== null)
			{
				$showedit= $this->canEditTc($RequestProduct->request);
				if($showedit==0){
					return $responsedata;
				}
				
				$wastage_percentage = $data['wastage_percentage'];
				$net_weight = $RequestProduct->net_weight;
				$additional_weight = $RequestProduct->additional_weight;
				$net_weight = $net_weight - $additional_weight;
				//$wastage_weight = ($net_weight*$wastage_percentage)/100;
				//$wastage_weight = ($net_weight/(100-$wastage_percentage))*100;
				if($wastage_percentage>0){
					$wastage_weight = (($net_weight/(100-$wastage_percentage))*100 ) - $net_weight;
				}else{
					$wastage_weight = 0.00;
				}
				

				$RequestProduct->wastage_weight =  $wastage_weight;
				$RequestProduct->wastage_percentage = $wastage_percentage;
				//$additional_weight = $RequestProduct->additional_weight;

				//$RequestProduct->total_net_weight = number_format($net_weight+$wastage_weight-$additional_weight,2,'.','');
				$RequestProduct->total_net_weight = number_format($net_weight+$wastage_weight,2,'.','');
				if($RequestProduct->total_net_weight<0){
					return $responsedata = ['status'=>0,'message'=>'Raw Material Required must be greater than 1'];
				}else if($RequestProduct->save())
				{
					$productStatus=0;
					if($RequestProduct->total_used_weight>=$RequestProduct->total_net_weight)
					{
						$productStatus=$RequestProduct->arrEnumStatus['input_added'];
					}else{
						$productStatus=$RequestProduct->arrEnumStatus['open'];
					}	
					$RequestProduct->status = $productStatus;
					$RequestProduct->save();
				}
				
				// Update Product Weight To Request Table		
				$this->updateProductWeightToRequest($RequestProduct->tc_request_id);
				
				$productdetails = $this->gettcproductdata($data['tc_request_product_id']);

				$responsedata = ['status'=>1,'message'=>'Wastage percentage updated successfully','productdetails'=>$productdetails];
			}
			
		}
		return $responsedata;
	}

	public function actionChangeAdditionalWeight(){
		
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		$data = yii::$app->request->post();		
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		if($data)
		{
			$userData = Yii::$app->userdata->getData();
			$userid=$userData['userid'];
			$user_type=$userData['user_type'];
			$role=$userData['role'];
			$rules=$userData['rules'];
			$resource_access=$userData['resource_access'];
			$is_headquarters =$userData['is_headquarters'];
			$franchiseid=$userData['franchiseid'];
			$role_chkid=$userData['role_chkid'];

			$RequestProduct = RequestProduct::find()->where(['id'=>$data['tc_request_product_id'] ])->one();
			if($RequestProduct !== null)
			{
				$showedit= $this->canEditTc($RequestProduct->request);
				if($showedit==0){
					return $responsedata;
				}
				
				$additional_weight = $data['additional_weight'];
				$cur_net_weight = $RequestProduct->net_weight;
				$cur_net_weight = $cur_net_weight - $additional_weight;				
				$wastage_percentage = $RequestProduct->wastage_percentage;										
				
				$cur_wastage_weight = 0;				
				// ------- Calculate the Wastage Weight Based on Additional Weight Code Start Here --------			
				if($wastage_percentage>0){
					$cur_wastage_weight = (($cur_net_weight/(100-$wastage_percentage))*100 ) - $cur_net_weight;
					$RequestProduct->wastage_weight =  $cur_wastage_weight;				
				}else{
					$RequestProduct->wastage_weight =  $cur_wastage_weight;				
				}				
				// ------- Calculate the Wastage Weight Based on Additional Weight Code Start Here --------
				
				//return $responsedata = ['status'=>0,'message'=>$wastageW.''.$cur_net_weight.'---'.$additional_weight.'---'.$cur_wastage_weight];
				
				//$total_net_weight = $cur_net_weight + $cur_wastage_weight - $additional_weight;
				$total_net_weight = $cur_net_weight + $cur_wastage_weight;
				//$total_net_weight = (($cur_net_weight + $cur_wastage_weight) / ( 100 - $additional_weight)) * 100;

				$RequestProduct->additional_weight = number_format($additional_weight,2,'.','');
				$RequestProduct->total_net_weight = number_format($total_net_weight,2,'.','');
				if($total_net_weight<0.001){
					return $responsedata = ['status'=>0,'message'=>'Raw Material Required must be greater'];
				}else if($RequestProduct->save())
				{
					$productStatus=0;
					if($RequestProduct->total_used_weight>=$RequestProduct->total_net_weight)
					{
						$productStatus=$RequestProduct->arrEnumStatus['input_added'];
					}else{
						$productStatus=$RequestProduct->arrEnumStatus['open'];
					}	
					$RequestProduct->status = $productStatus;
					$RequestProduct->save();
				}
				
				// Update Product Weight To Request Table		
				$this->updateProductWeightToRequest($RequestProduct->tc_request_id);
				
				$productdetails = $this->gettcproductdata($data['tc_request_product_id']);

				$responsedata = ['status'=>1,'message'=>'Additional weight updated successfully','productdetails'=>$productdetails];
			}
			
		}
		return $responsedata;
	}
	
	public function gettcproductdata($tc_request_product_id){

		$pdtdata = RequestProduct::find()->where(['id'=>$tc_request_product_id])->one();
		$productdata = [];
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		if($pdtdata !== null){
			//foreach ($RequestProduct as $pdtdata) {
			$productname = '';

			$Unitproduct = $pdtdata->unitproduct;
			$completepdtname = '';
			if($Unitproduct!== null){
				$productstd = $Unitproduct->product;
				if($productstd!==null){
					$standard_name = $productstd->standard->name;
					$labelgradename = $productstd->label_grade_name;

					$productname = $productstd->appproduct->product_name;
					$producttypename = $productstd->appproduct->product_type_name;

					$wastage = $productstd->appproduct->wastage;
					$materialcompositionname = '';
					if(count($productstd->productmaterial) >0){
						foreach($productstd->productmaterial as $productmaterial){

							$productMaterialList[]=[
								'app_product_id'=>$productmaterial->app_product_id,
								'material_id'=>$productmaterial->material_id,
								'material_name'=>$productmaterial->material_name,
								'material_type_id'=>$productmaterial->material_type_id,
								'material_type_name'=> $productmaterial->material_type_name,//material->material_type[$productmaterial->material_type_id],
								'material_percentage'=>$productmaterial->percentage
							];
							$materialcompositionname = $materialcompositionname.$productmaterial->percentage.'% '.$productmaterial->material_name.' + ';

						}
						$materialcompositionname = rtrim($materialcompositionname," + ");
					}
					$completepdtname = $productname.' | '.$producttypename.' | '.$wastage.'% wastage | '.$materialcompositionname.' | '.$standard_name.' | '.$labelgradename;
					//.' | '.$productname.' | '.$productname.' | '.$productname;
					//$wastage = $productstd->appproduct->product->wastage;

					//$producttypename = $productstd->appproduct->producttype->name;
					//$producttypename = $productstd->appproduct->producttype->name;
					//$producttypename = $productstd->appproduct->producttype->name;


					//acc | org acc | 22% wastage | 100% organic | gots | organic
				}
				
				
			}

			$materialused = [];
			$totalweightusedfrommaterial=0;
			$TcRawMaterialUsedWeight = TcRawMaterialUsedWeight::find()->where(['tc_request_product_id'=>$pdtdata->id])->all();
			if(count($TcRawMaterialUsedWeight)>0){
				foreach($TcRawMaterialUsedWeight as $UsedWeightObj){
					$totalweightusedfrommaterial+=$UsedWeightObj->used_weight;
					$materialused[] = [
						'stock_weight' => $UsedWeightObj->stock_weight,
						'used_weight' =>  $UsedWeightObj->used_weight,
						'remaining_weight' => $UsedWeightObj->remaining_weight,
						'supplier_name' => $UsedWeightObj->rawmaterial->supplier_name,						
						'tc_number' => $UsedWeightObj->rawmaterial->tc_number,
						'tc_attachment' => $UsedWeightObj->rawmaterial->tc_attachment,
						'invoice_attachment' => $UsedWeightObj->rawmaterial->tc_number==''?$UsedWeightObj->rawmaterial->invoice_attachment:'',
						'declaration_attachment' => $UsedWeightObj->rawmaterial->tc_number==''?$UsedWeightObj->rawmaterial->declaration_attachment:'',
						'raw_material_id' => $UsedWeightObj->tc_raw_material_id,
						'raw_material_product_id' => $UsedWeightObj->tc_raw_material_product_id,
						'trade_name' => $UsedWeightObj->rawmaterialproduct->trade_name,
						'product_name' => $UsedWeightObj->rawmaterialproduct->product_name,
						'lot_number' => $UsedWeightObj->rawmaterialproduct->lot_number
					];
				}
			}

			$rawmaterialusedlist = [];
			if(count($pdtdata->usedweight)>0){
				foreach($pdtdata->usedweight as $materialpdtused){
					$rawmaterialusedlist[]=[
						'tc_raw_material_id' => $materialpdtused->tc_raw_material_id,
						'tc_raw_material_product_id' => $materialpdtused->tc_raw_material_product_id,
						'product_id' => $materialpdtused->product_id,
						'stock_weight' => $materialpdtused->stock_weight,
						'used_weight' => $materialpdtused->used_weight,
						'remaining_weight' => $materialpdtused->remaining_weight
					];
				}
			}
			$productdata = [
				'id' => $pdtdata->id,
				'tc_request_id' => $pdtdata->tc_request_id,
				'trade_name' => $pdtdata->trade_name,
				'product_id' => $pdtdata->product_id,
				'product_name' => $completepdtname,
				'packed_in' => $pdtdata->packed_in,
				'lot_ref_number' => $pdtdata->lot_ref_number,
				'consignee_id' => $pdtdata->consignee_id,

				'unit_information' => $pdtdata->unit_information,
				'purchase_order_no' => $pdtdata->purchase_order_no,
				'purchase_order_date' => date($date_format,strtotime($pdtdata->purchase_order_date)),
				'invoice_no' => $pdtdata->invoice_no,
				'transport_document_no' => $pdtdata->transport_document_no,
				'transport_company_name' => $pdtdata->transport_company_name?:'NA',
				'vehicle_container_no' => $pdtdata->vehicle_container_no?:'NA',
				'invoice_date' => date($date_format,strtotime($pdtdata->invoice_date)),
				'transport_document_date' => date($date_format,strtotime($pdtdata->transport_document_date)),
				'production_date' => date($date_format,strtotime($pdtdata->production_date)),
				'transport_id' => $pdtdata->transport_id,
				'transport_id_label' => $pdtdata->transport?$pdtdata->transport->name:'',
				'wastage_percentage' => $pdtdata->wastage_percentage,
				'gross_weight' => $pdtdata->gross_weight,
				'net_weight' => $pdtdata->net_weight,
				'certified_weight' => $pdtdata->certified_weight,
				'wastage_weight' => $pdtdata->wastage_weight,
				'additional_weight' => $pdtdata->additional_weight,
				'total_net_weight' => $pdtdata->total_net_weight,
				'total_used_weight' => $pdtdata->total_used_weight,
				'product_status'=> $pdtdata->status,
				'totalweightusedfrommaterial' => $totalweightusedfrommaterial,
				'materialused' => $materialused,
				'rawmaterialusedlist' => $rawmaterialusedlist
				
				
			];
			//}
		}
		return $productdata;
	}	

	public function actionCopyrequestdetails(){

		$data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($data && $data['id']) 
		{
			$userData = Yii::$app->userdata->getData();
			$modelorg = Request::find()->where(['id' => $data['id']])->one();
			
			$cancopy = $this->canCopyTc($modelorg);
			if($cancopy == 0){
				return $responsedata;
			}
			if($modelorg !== null){
 
				$model = new Request();

				$model->company_name = $modelorg->company_name;	
				$model->unit_name = $modelorg->unit_name;	
				$model->address_id = $modelorg->address_id;	
				

				$model->app_id = $modelorg->app_id;	
				$model->unit_id = $modelorg->unit_id;	

				$model->buyer_id = $modelorg->buyer_id;	
				$model->consignee_id = $modelorg->consignee_id;
				$model->standard_id = $modelorg->standard_id;	
				$model->purchase_order_number = $modelorg->purchase_order_number;	
				$model->inspection_body_id = $modelorg->inspection_body_id;	
				$model->country_of_dispach = $modelorg->country_of_dispach;
				$model->country_of_destination = $modelorg->country_of_destination;			
				$model->usda_nop_compliant = $modelorg->usda_nop_compliant;	
				//$model->apeda_npop_compliant = $modelorg->apeda_npop_compliant;	
				$model->comments = $modelorg->comments;	
				$model->declaration = $modelorg->declaration;	
				$model->additional_declaration = $modelorg->additional_declaration;	
				$model->standard_declaration = $modelorg->standard_declaration;	
				
				$model->grand_total_net_weight = $modelorg->grand_total_net_weight;
				$model->total_wastage_weight = $modelorg->total_wastage_weight;
				$model->total_certified_weight = $modelorg->total_certified_weight;
				$model->total_net_weight = $modelorg->total_net_weight;
				$model->total_gross_weight = $modelorg->total_gross_weight;
				//$model->visible_to_brand = $modelorg->visible_to_brand;
				//$model->transport_id = $modelorg->transport_id;

				$model->status = 0;
				$model->created_by = $userData['userid'];			
				if($model->validate() && $model->save())
				{	
					

					$manualID = $model->id;

					//$model->tc_number_temp = $manualID;
					//$model->save();

					$existingstandard = [];
					$RequestStandard = RequestStandard::find()->where(['tc_request_id' =>  $data['id'] ])->all();
					if(count($RequestStandard)>0){
						foreach($RequestStandard as $rstandard){
							$requeststd = new RequestStandard();
							$requeststd->tc_request_id = $manualID;
							$requeststd->standard_id = $rstandard->standard_id;
							$requeststd->save();
						}
					}

					$ifoamStandard = TcRequestIfoamStandard::find()->where(['tc_request_id' =>  $data['id'] ])->all();
					if(count($ifoamStandard)>0)
					{
						foreach ($ifoamStandard as $value)
						{ 
							$requeststd = new TcRequestIfoamStandard();
							$requeststd->tc_request_id = $manualID;
							$requeststd->ifoam_standard_id = $value->ifoam_standard_id;
							$requeststd->save();
						}
					}
					

					$TcRequestProductModel = RequestProduct::find()->where(['tc_request_id'=>$data['id']])->all();
			 		if(count($TcRequestProductModel)>0)
					{
						foreach($TcRequestProductModel as $productModel){
							$RequestProduct = new RequestProduct();
							$RequestProduct->tc_request_id = $manualID;

							$RequestProduct->standard_id = $productModel->standard_id;
							$RequestProduct->product_id = $productModel->product_id;	
							$RequestProduct->trade_name = $productModel->trade_name;	
							$RequestProduct->packed_in = $productModel->packed_in;
							$RequestProduct->lot_ref_number = $productModel->lot_ref_number;
							$RequestProduct->consignee_id = $productModel->consignee_id;
							
								
							$RequestProduct->gross_weight = $productModel->gross_weight;
							$RequestProduct->net_weight = $productModel->net_weight;	
							$RequestProduct->certified_weight = $productModel->certified_weight;	
							$RequestProduct->wastage_percentage = $productModel->wastage_percentage;
							$RequestProduct->product_wastage_percentage = $productModel->product_wastage_percentage;

							$RequestProduct->unit_information = $productModel->unit_information;
							$RequestProduct->purchase_order_no = $productModel->purchase_order_no;
							$RequestProduct->purchase_order_date = $productModel->purchase_order_date;							
							$RequestProduct->invoice_no = $productModel->invoice_no;
							$RequestProduct->invoice_date = $productModel->invoice_date;
							$RequestProduct->transport_document_no = $productModel->transport_document_no;
							$RequestProduct->transport_company_name = $productModel->transport_company_name;
							$RequestProduct->vehicle_container_no = $productModel->vehicle_container_no;

							$RequestProduct->transport_document_date =  $productModel->transport_document_date;
							$RequestProduct->production_date = $productModel->production_date;
							$RequestProduct->transport_id = $productModel->transport_id;
							 
							$RequestProduct->wastage_weight = $productModel->wastage_weight;	
							$RequestProduct->additional_weight = $productModel->additional_weight;
							$RequestProduct->total_net_weight = $productModel->total_net_weight;
							
							$RequestProduct->transport_company_name = $productModel->transport_company_name;
							$RequestProduct->vehicle_container_no = $productModel->vehicle_container_no;
							
							$RequestProduct->created_by = $userData['userid'];
							$RequestProduct->status = 0;
							$RequestProduct->save();
						}
					}
					
					if(count($modelorg->evidence)>0)
					{
						$target_dir = Yii::$app->params['tc_files']."evidence_files/"; 
						foreach($modelorg->evidence as $evidence)
						{
							$evidence_file='';
							if($evidence->evidence_file!='')
							{
								$evidence_file=Yii::$app->globalfuns->copyFiles($evidence->evidence_file,$target_dir);
								$RequestEvidence = new RequestEvidence();
								$RequestEvidence->evidence_file = $evidence_file;
								$RequestEvidence->tc_request_id = $manualID;
								$RequestEvidence->evidence_type = $evidence->evidence_type;
								$RequestEvidence->save();
							}

							
						}
						
					}


				}
				$responsedata = ['status'=>1,'message'=>'TC Request cloned successfully','newid'=> $manualID ];
			}
		}
		return $responsedata;	
	}

	public function actionGetaddressdetails(){

		$data = Yii::$app->request->post();
		$responsedata=array('status'=>0,'message'=>'Something went wrong! Please try again');
		if ($data && $data['id']) 
		{
			$addressdetails = [];
			//if($data['type'] == 'buyer'){
				$Buyer = Buyer::find()->where(['id'=>$data['id']])->one();
				if($Buyer !== null){
					$addressdetails['address'] = $Buyer->address;
					$addressdetails['country'] = $Buyer->country->name;
					$addressdetails['state'] = $Buyer->state->name;
					$addressdetails['city'] = $Buyer->city;
					$addressdetails['zipcode'] = $Buyer->zipcode;
					$addressdetails['email'] = $Buyer->email;
					$addressdetails['phonenumber'] = $Buyer->phonenumber;

				}
			//}
			//$userData = Yii::$app->userdata->getData();
			//$modelorg = Request::find()->where(['id' => $data['id']])->one();
			
		}
		return $responsedata;
	}
	
}
