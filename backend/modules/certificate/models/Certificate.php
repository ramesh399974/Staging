<?php

namespace app\modules\certificate\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

use app\modules\master\models\User;
use app\modules\master\models\Standard;
use app\modules\master\models\ReductionStandard;
use app\modules\master\models\ProductTypeMaterialComposition;

use app\modules\application\models\ApplicationUnitCertifiedStandard;
use app\modules\application\models\Application;
use app\modules\application\models\ApplicationRenewal;
use app\modules\application\models\ApplicationStandard;
use app\modules\application\models\ApplicationUnit;
use app\modules\application\models\ApplicationUnitStandard;
use app\modules\application\models\ApplicationCertifiedByOtherCB;
use app\modules\application\models\ApplicationProductCertificateTemp;

use app\modules\audit\models\Audit;
use app\modules\audit\models\AuditReviewerRiskCategory;

use app\modules\changescope\models\ProductAddition;
use app\modules\changescope\models\Withdraw;
use app\modules\audit\models\AuditPlan;
use app\modules\audit\models\AuditPlanUnit;
/**
 * This is the model class for table "tbl_certificate".
 *
 * @property int $id
 * @property int $audit_id
 * @property int $status 0=Open,1=Certification In-Process,2=Certificate Generated,3=Declined
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_by
 * @property int $updated_at
 */
class Certificate extends \yii\db\ActiveRecord
{
	public $arrStatus=array('0'=>'Open','1'=>'Certification In-Process','2'=>'Certified','3'=>'Declined','4'=>'Suspension','5'=>'Cancellation','6'=>'Withdrawn','7'=>'Extension','8'=>'Certificate Reinstate','9'=>'Certified by Other CB yet to be expired','10'=>'Expired','11'=>'Extension for TC');
    public $arrEnumStatus=array('open'=>'0','certification_in_process'=>'1','certificate_generated'=>'2','declined'=>'3','suspension'=>'4','cancellation'=>'5','withdrawn'=>'6','extension'=>'7','certificate_reinstate'=>'8','certified_by_other_cb'=>'9','expired'=>'10','extension_for_tc'=>'11');    
    public $arrStatusColor=array('0'=>'#4572A7','1'=>"#DB843D",'2'=>'#5f79fa','3'=>'#ff0000','4'=>'#4572A7','5'=>'#DB843D','6'=>'#5f79fa','7'=>'#457222','8'=>'#eeeeee','9'=>'#DB843D','10'=>'#DB843D');
	public $arrteStandardPolicy=array('1'=>'V1.1','2'=>'V1.2');
	public $arrccsPolicy=array('1'=>'V2.0','2'=>'V3.1');
    
    public $arrCertificateStatus=array('0'=>'Valid','1'=>'In-Valid');
	public $arrEnumCertificateStatus=array('valid'=>'0','invalid'=>'1');
	public $arrCertificateStatusForList=array('0'=>'Yes','1'=>'No');
	
	public $arrType=array('1'=>'Normal','2'=>'Renewal','3'=>'Process Addition','4'=>'Standard Addition','5'=>'Unit Addition','6'=>'Change of Address','7'=>'Withdraw of Unit','8'=>'Product Addition');
	public $arrEnumType=array('normal'=>'1','renewal'=>'2','process_addition'=>'3','standard_addition'=>'4','unit_addition'=>'5','change_of_address'=>'6','withdraw_unit'=>'7','product_addition'=>'8');
	public $due_days; 
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_certificate';
    }

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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['audit_id', 'status', 'created_by', 'created_at', 'updated_by', 'updated_at'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'audit_id' => 'Audit ID',
            'status' => 'Status',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_by' => 'Updated By',
            'updated_at' => 'Updated At',
        ];
    }
	
	public function getCertificatebyotherbody()
    {
        return $this->hasOne(ApplicationCertifiedByOtherCB::className(), ['app_id' => 'parent_app_id','standard_id'=>'standard_id']);
	}
	
	public function getCertificatefiles()
    {
        return $this->hasMany(CertificateFiles::className(), ['certificate_id' => 'id']);
    }
	
	public function getAudit()
    {
        return $this->hasOne(Audit::className(), ['id' => 'audit_id']);
    }

    public function getCertificatereview()
    {
        return $this->hasOne(CertificateReviewerReview::className(), ['certificate_id' => 'id']);
    }
	
	public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    public function getExtensionby()
    {
        return $this->hasOne(User::className(), ['id' => 'extension_by']);
    }
	
	public function getGeneratedby()
    {
        return $this->hasOne(User::className(), ['id' => 'certificate_generated_by']);
    }	
	
	public function getReviewer()
    {
        return $this->hasOne(CertificateReviewer::className(), ['certificate_id' => 'id'])->andOnCondition(['reviewer_status' => 1]);
    }

    public function getReviewcertificate()
    {
        return $this->hasMany(CertificateStatusReview::className(), ['certificate_id' => 'id']);
    }

    public function getStandard()
    {
        return $this->hasOne(Standard::className(), ['id' => 'standard_id']);
    }

	public function getCertificatestandards()
    {
        return $this->hasMany(CertificateStandards::className(), ['certificate_id' => 'id']);
    }

    public function getApplication()
    {
        return $this->hasOne(Application::className(), ['id' => 'parent_app_id']);
    }
	
	public function getProductaddition()
    {
        return $this->hasOne(ProductAddition::className(), ['id' => 'product_addition_id']);
    }
	
	public function getWithdraw()
    {
        return $this->hasOne(Withdraw::className(), ['id' => 'withdraw_id']);
	}

	public function getRiskcategory()
    {
        return $this->hasOne(AuditReviewerRiskCategory::className(), ['id' => 'risk_category']);
	}

	public function getRenewaldetails()
    {
        return $this->hasOne(ApplicationRenewal::className(), ['new_app_id' => 'parent_app_id']);
	}

	

	public function sortByOrder($a, $b) {
		return $a['material_percentage'] - $b['material_percentage'];
	}
	
	public function generateCertificate($certificateID,$returnType=false)
	{
		$certificatemodel = new Certificate();
		$applicationmodel = new Application();
		
		$connection = Yii::$app->getDb();
		$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();
		
		$certificateDraftNo=0;
		$model = Certificate::find()->where(['id' => $certificateID])->one();
		if($model !== null)
		{	
			$certificateDraftNo=$model->version;
			$application = $model->audit->application;
			$parent_app_id = $model->audit->application->parent_app_id;
			$current_app_id = $model->parent_app_id;
			$audit_type = $application->audit_type;
			$cert_audit_id = $model->audit_id;
			$cert_parent_app_id =  $model->parent_app_id;
			$certificate_audit_type = $model->type;

			$ccs_version = $model->ccs_version?$certificatemodel->arrccsPolicy[$model->ccs_version]:'';
			$te_standard_version = $model->te_standard_version?$certificatemodel->arrteStandardPolicy[$model->te_standard_version]:'';
			
			$getCertifiedDateModel = Certificate::find()->alias('t')->where(['t.parent_app_id' => $model->parent_app_id,'t.certificate_status'=>0,'t.status'=>array($certificatemodel->arrEnumStatus['certificate_generated'],$certificatemodel->arrEnumStatus['extension'])]);
			$getCertifiedDateModel = $getCertifiedDateModel->join('inner join','tbl_certificate_standards as cstd','cstd.certificate_id=t.id')->orderBy(['t.id' => SORT_DESC])->one();
			if(!$returnType)
			{
				$certificate_generate_date = date("Y-m-d",time());							
				if($getCertifiedDateModel != null)
				{
					$certificate_generate_date = date('Y-m-d',strtotime($certificate_generate_date));
					$certificate_expiry_date = date('Y-m-d', strtotime($getCertifiedDateModel->certificate_valid_until));
					
				
					$previous_certificate_generated_year = date('Y', strtotime($getCertifiedDateModel->certificate_generated_date));
					$current_date=date("Y");     
					// Renewal Applications
					if($certificate_audit_type == 2){
						$certificateDraftNo= 1;
					}else	if(strtotime($current_date) > strtotime($previous_certificate_generated_year)) { 
						$certificateDraftNo= 1;
					}					
					else{
						$certificateDraftNo = $getCertifiedDateModel->version+1;
					}

					// $certificateDraftNo = $getCertifiedDateModel->version+1;					
				}else if(($model->product_addition_id=='' || $model->product_addition_id==null) && $audit_type==$applicationmodel->arrEnumAuditType['renewal']){
					$certificateDraftNo=1;
					$renewal_parent_app_id=$parent_app_id;
					
					$renewals_parent_audit_type=$audit_type;
					$appstandards = ApplicationStandard::find()->where(['app_id'=>$current_app_id])->all();
					$standardids = [];
					if(count($appstandards)>0){
						foreach($appstandards as $appstd){
							$standardids[] = $appstd->standard_id;
						}
					}
					$renewals_app_mod = Application::find()->where(['id'=>$renewal_parent_app_id])->one();
					if($renewals_app_mod!==null){
						$renewals_parent_audit_type = $renewals_app_mod->audit_type;
						if($renewals_parent_audit_type!=$applicationmodel->arrEnumAuditType['renewal'] && $renewals_parent_audit_type!=$applicationmodel->arrEnumAuditType['normal'] ){
							$renewal_parent_app_id = $renewals_app_mod->parent_app_id;
						}
					}
					
					$getReneCertifiedDateModel = Certificate::find()->where(['parent_app_id' => $renewal_parent_app_id,'standard_id'=>$model->standard_id,'type'=>[1,2,4]])->orderBy(['id' => SORT_DESC])->one();
					
					if($getReneCertifiedDateModel!=null){
						$certificate_generate_date = date("Y-m-d",time());
						$certificate_generate_date = date('Y-m-d',strtotime($certificate_generate_date));

						$renewal_future_date = $getReneCertifiedDateModel->certificate_valid_until;
						$certificate_valid_until = date('Y-m-d', strtotime('+1 year', strtotime($renewal_future_date)));
						$certificate_expiry_date = $certificate_valid_until;
					}else{
						$futureDate = date('Y-m-d', strtotime('+1 year', strtotime($certificate_generate_date)) );
					    $certificate_generate_date = date('Y-m-d',strtotime($certificate_generate_date));					
					    $certificate_expiry_date = date('Y-m-d', strtotime('-1 day', strtotime($futureDate)));
					}
				}
				else{
					$futureDate = date('Y-m-d', strtotime('+1 year', strtotime($certificate_generate_date)) );
					$certificate_generate_date = date('Y-m-d',strtotime($certificate_generate_date));					
					$certificate_expiry_date = date('Y-m-d', strtotime('-1 day', strtotime($futureDate)));						
				}	
			}else{
				$certificate_generate_date = date('Y-m-d', strtotime($model->certificate_generated_date));
				$certificate_expiry_date = date('Y-m-d', strtotime($model->certificate_valid_until));
			}

			if($certificateDraftNo==0)
			{
				$certificateDraftNo=1;
			}


		
			$applicationID = $model->application->id;
			$last_updated_date = date("Y-m-d",time());
			//New TE Template Update // Last Update date logic for Unit,Product,Process Addition and Change of Scope  Certificates 
			if($model->standard_id != 1 && $certificate_audit_type == 8 || $certificate_audit_type == 5 || $certificate_audit_type == 3 || $certificate_audit_type == 6)// Product Addition and Site Addition
			{
				//$CertificateExist = Certificate::find()->where(['parent_app_id' => $model->parent_app_id,'standard_id'=>$model->standard_id,'certificate_status'=>[0,1],
				//'status'=>array($certificatemodel->arrEnumStatus['certificate_generated'],$certificatemodel->arrEnumStatus['extension'])]);
				//$CertificateExist = $CertificateExist->andWhere(['not in','id',$model->id])->orderBy(['id' => SORT_DESC])->one();
				
				$CertificateExist = Certificate::find()->where(['parent_app_id' => $model->parent_app_id,'standard_id'=>$model->standard_id,'type'=>[1,2],'certificate_status'=>[0,1],
				'status'=>array($certificatemodel->arrEnumStatus['certificate_generated'],$certificatemodel->arrEnumStatus['extension'])])
				->orderBy(['id' => SORT_DESC])
				->one();
				
				if($CertificateExist!==null){
					$parent_application_generated_date = $CertificateExist->certificate_generated_date;	
					$last_updated_date = $parent_application_generated_date;
					//$last_updated_date = date("Y-m-d",time());
				}
			
			}		
			
			//Code to remove product unselected by the reviewer starts
			$removeproductIds = [];
			$ApplicationProductCertificateTemp = ApplicationProductCertificateTemp::find()->where(['app_id'=>$applicationID,'certificate_id'=>$certificateID])->all();
			if(count($ApplicationProductCertificateTemp) >0){
				foreach($ApplicationProductCertificateTemp as $tempproduct){
					$removeproductIds[] = $tempproduct->product_id;
				}
			}
			$removeproductCondition = '';
			if(count($removeproductIds)>0){
				$removeproductCondition = ' AND ap.id not in ('.implode(',',$removeproductIds).') ';
			}
			//Code to remove product unselected by the reviewer ends

			$certifiedprdunitsarr = array();
			$applicationunitmod = ApplicationUnit::find()->where(['app_id'=>$applicationID,'status'=>0])->all();
			if(count($applicationunitmod)>0)
			{
				foreach($applicationunitmod as $units)
				{
					if($units->unit_type==3 && count($units->unitstandard)>0){
						continue;
					}
					$certifiedprdunitsarr[]=$units->id;
				}
			}
			$certifiedprdunits = implode(',',$certifiedprdunitsarr);
			$ospnumber = $model->audit->application->franchise->usercompanyinfo->osp_number;
			$customeroffernumber = $model->audit->application->customer->customer_number;
			$usercompanyinfoObj = $model->audit->application->customer->usercompanyinfo;
			
			
			// ----------- Getting the company name latest code start here  ----------------------
			$companyName='';
			$companyAddress='';
			$companyAddress_town_postcode='';
			$companyAddress_state_country='';

			$applicationModelObject = $model->application->currentaddress;
			$companyName = $applicationModelObject->company_name;														
			$companyAddress = trim($applicationModelObject->address).', ';						
			// $companyAddress.= $applicationModelObject->city.', '.$applicationModelObject->zipcode.', ';
			// $companyAddress.= $applicationModelObject->state->name.', '.$applicationModelObject->country->name;
			$companyAddress_town_postcode = $applicationModelObject->city.', '.$applicationModelObject->zipcode;
			$companyAddress_state_country = $applicationModelObject->state->name.', '.$applicationModelObject->country->name;
			// ----------- Getting the company name latest code end here  ----------------------	
				
			$standardName = '';
			$standardCode = '';
			$standardScode = '';				
			$date_format = Yii::$app->globalfuns->getSettings('date_format');

			$appStd = $model->certificatestandards;
			$arrAppStd = [];
			if(count($appStd)>0)
			{	
				foreach($appStd as $app_standard)
				{
					$arrAppStd[]=$app_standard->standard->id;
				}
			}
			$productTypeMaterialComposition = new ProductTypeMaterialComposition(); 
			$modelApplicationStandard = new ApplicationStandard();			
			$standard_id = $arrAppStd;
			$app_id = $model->parent_app_id;
			$appstandard = ApplicationStandard::find()->where(['app_id'=>$app_id,'standard_status'=>array($modelApplicationStandard->arrEnumStatus['valid'],$modelApplicationStandard->arrEnumStatus['draft_certificate'])])->all();
			if(count($appstandard) > 0)
			{
				// $standard_id = $appstandard->standard->id;
				// $standardName = $appstandard->standard->name.' ('.$appstandard->standard->code.')';
				
				$parentappstdmod = $appstandard;
				$standardScode ='';
				$standardCode ='';
				$standardVersion = '';
				
				if(count($standard_id)==1 && in_array('1',$standard_id)){
					foreach($standard_id as $gstd){
						$gstdmod = Standard::find()->where(['id'=>$gstd,'status'=>0])->one();
						if($gstdmod!==null){
							$standardScode = $gstdmod->short_code;
							$standardCode =  $gstdmod->code;
							$standardVersion = $gstdmod->version;
							$standardName = $gstdmod->name;
						}
					}
					$standard_id = $standard_id;

				}else{
					$te_standards =[];
					$te_standardName = [];
					$te_standardVersions = [];
					$te_standardNameWithVersions = [];
					$te_auditCriteriaStandardVersion=[];
					if(count($parentappstdmod)>0){
						foreach($parentappstdmod as $pstd){
							if($pstd->standard_id!=1 && !in_array($pstd->standard_id,$te_standards)){
								$te_standards[] = $pstd->standard_id;
								$te_standardName[] = $pstd->standard->name;
								$te_standardVersions[] = $pstd->version;
								$te_standardNameWithVersions[] = strtoupper($pstd->standard->name).' (Version'.$pstd->version.')';
								$te_auditCriteriaStandardVersion[] = ($pstd->standard->name).' (V'.$pstd->version.')';
							}
						}
					}

					if(count($te_standards)>1){
						$standardScode ='MUL';
						$standardCode ='MUL';
					}elseif(count($te_standards)==1){
						foreach($te_standards as $tstd){
							$testdmod = Standard::find()->where(['id'=>$tstd,'status'=>0])->one();
							if($testdmod!==null){
								$te_standards[] = $testdmod->standard_id;
								$te_standardName[] = $testdmod->name;
								$te_standardVersions[] = $testdmod->version;
								$te_standardNameWithVersions[] = strtoupper($testdmod->name).' (Version'.$testdmod->version.')';
								$te_auditCriteriaStandardVersion[] = ($testdmod->name).' (V'.$testdmod->version.')';
							}	
						}
					}

					if(count($te_standards)>0){
						$standard_id = $te_standards;
					}
				}

				// $standardName = $appstandard->standard->name;
				// $standardVersion = $appstandard->version;
				// $standardCode = $appstandard->standard->code;
				// $standardScode = $appstandard->standard->short_code;
				
				$RegistrationNo = "GCL-".$ospnumber.$standardScode.$customeroffernumber;
				$LicenseNo=$customeroffernumber;
				//$certificateNumber = "GCL-".$customeroffernumber."/".$standardCode."-".date("Y")."/".$certificateDraftNo;
				//$certificateNumber = $customeroffernumber;
				$certificateNumber = "GCL-".$customeroffernumber.'-'.$standardCode."-".date("y").date("m");
				
				$model->code=$RegistrationNo;
				
			
				//$productsQry = 'SELECT ap.product_name AS product,prd.code AS prod_code,prdtype.code AS prod_type_code,ap.product_type_name AS product_type, appunit.unit_id AS unit_id,std.code AS standard_code,GROUP_CONCAT(DISTINCT apm.percentage, \'% \', ptm.`name` SEPARATOR \' + \') AS material_composition
				// ,GROUP_CONCAT(DISTINCT apm.percentage, \'@@\', apm.`material_name`, \'@@\', apm.material_type_id, \'@@\', ptm.code SEPARATOR \'$$\') AS material_composition_comb ,slg.name 
				// AS product_code,slg.id as product_label_grade_id  FROM `tbl_application_product` AS ap
				//INNER JOIN `tbl_application_product_material` AS apm ON apm.app_product_id = ap.id AND ap.app_id='.$applicationID.' '.$removeproductCondition.' 
				//INNER JOIN `tbl_application_product_standard` AS aps ON aps.application_product_id = ap.id AND aps.standard_id='.$standard_id.' AND aps.product_standard_status=0
				//INNER JOIN `tbl_application_unit_product` AS appunit ON appunit.application_product_standard_id = aps.id
				//INNER JOIN `tbl_product` AS prd ON prd.id = ap.product_id
				//INNER JOIN `tbl_standard` AS std ON std.id = aps.standard_id
				//INNER JOIN `tbl_product_type` AS prdtype ON prdtype.id=ap.product_type_id
				//INNER JOIN `tbl_product_type_material` AS ptm ON ptm.id=apm.material_id
				//INNER JOIN `tbl_standard_label_grade` AS slg ON slg.id=aps.label_grade_id
				//GROUP BY apm.app_product_id,ap.id';
				
				$productsQry = 'SELECT ap.product_name AS product,prd.code AS prod_code,prdtype.code AS prod_type_code,ap.product_type_name AS product_type, GROUP_CONCAT(DISTINCT appunit.unit_id) AS unit_id,std.code AS standard_code,GROUP_CONCAT(DISTINCT apm.percentage, \'% \', ptm.`name` SEPARATOR \' + \') AS material_composition
				 ,GROUP_CONCAT(DISTINCT apm.percentage, \'@@\', apm.`material_name`, \'@@\', apm.material_type_id, \'@@\', ptm.code SEPARATOR \'$$\') AS material_composition_comb ,GROUP_CONCAT(DISTINCT CONCAT(std.code," (",slg.name,")")) 
				 AS product_code,slg.name AS got_product_code,slg.id as product_label_grade_id  FROM `tbl_application_product` AS ap
				INNER JOIN `tbl_application_product_material` AS apm ON apm.app_product_id = ap.id AND ap.app_id='.$applicationID.' '.$removeproductCondition.' 
				INNER JOIN `tbl_application_product_standard` AS aps ON aps.application_product_id = ap.id AND aps.standard_id IN ('.implode(',',$standard_id).') AND aps.product_standard_status=0
				INNER JOIN `tbl_application_unit` AS appunitmain ON appunitmain.app_id ='.$applicationID.' AND appunitmain.id IN ('.$certifiedprdunits.')
				INNER JOIN `tbl_application_unit_standard` AS appunitstd ON appunitstd.unit_id=appunitmain.id AND appunitstd.standard_id IN ('.implode(',',$standard_id).')

				INNER JOIN `tbl_application_unit_product` AS appunit ON appunit.application_product_standard_id = aps.id
				AND appunit.unit_id= appunitmain.id AND appunitmain.status=0							
				INNER JOIN `tbl_product` AS prd ON prd.id = ap.product_id
				INNER JOIN `tbl_standard` AS std ON std.id = aps.standard_id
				INNER JOIN `tbl_product_type` AS prdtype ON prdtype.id=ap.product_type_id
				INNER JOIN `tbl_product_type_material` AS ptm ON ptm.id=apm.material_id
				INNER JOIN `tbl_standard_label_grade` AS slg ON slg.id=aps.label_grade_id
				GROUP BY apm.app_product_id,ap.id';
				
				
				// material_composition product_code
												
				$command = $connection->createCommand($productsQry);
				$connection->createCommand("SET SESSION group_concat_max_len = 1000000;")->execute();
				$connection->createCommand("SET SQL_BIG_SELECTS = 1")->execute();
				$result = $command->queryAll();
				
				$labelGradeCnt=1;
				$arrLabelGrade=array();
				$arrCertificateCoveredProducts=array();
				$arrProductCategories=array();
				$productContent=''; 
				// print_r($result);
				if(count($result)>0)
				{
					foreach($result as $vals)
					{						
						$arrOrganicMaterial=array();						
						$arrOtherMaterial=array();						
						$material_composition_comb = $vals['material_composition_comb'];
						$material_composition_comb_array = explode('$$',$material_composition_comb);
						if(is_array($material_composition_comb_array) && count($material_composition_comb_array)>0)
						{
							$organic_material_array_key=0;
							$other_material_array_key=0;
							
							foreach($material_composition_comb_array as $mc)
							{
								$material_composition_array = explode('@@',$mc);
								if(is_array($material_composition_array) && count($material_composition_array)>0)
								{
									$material_composition_percentage = isset($material_composition_array[0])?$material_composition_array[0]:'';
									$material_composition_name = isset($material_composition_array[1])?$material_composition_array[1]:'';
									$material_composition_type_id = isset($material_composition_array[2])?$material_composition_array[2]:'';
									$material_composition_type_code = isset($material_composition_array[3])?$material_composition_array[3]:'';
									//if(stripos($material_composition_name, 'Organic') !== false)
									if($material_composition_type_id == $productTypeMaterialComposition->arrEnumMaterialType['certified'])		
									{
										$arrOrganicMaterial[$organic_material_array_key]=array('material_name'=>$material_composition_name,'material_percentage'=>$material_composition_percentage,'material_code'=>$material_composition_type_code);
										$organic_material_array_key++;									
									}else{	
									    $arrOtherMaterial[$other_material_array_key]=array('material_name'=>$material_composition_name,'material_percentage'=>$material_composition_percentage,'material_code'=>$material_composition_type_code);
										$other_material_array_key++;					
									}										
								}
							}
							
							$materialCompositionContent='';
							if(is_array($arrOrganicMaterial) && count($arrOrganicMaterial)>0)
							{
								//usort($arrOrganicMaterial, 'sortByOrder');
								usort($arrOrganicMaterial, function($a, $b) {
									return $b['material_percentage'] <=> $a['material_percentage'];
								});
								
								foreach($arrOrganicMaterial as $arrOM)
								{
									$materialCompositionContent.=$arrOM['material_percentage'].'% '.$arrOM['material_name'].' ('.$arrOM['material_code'].') + ';
								}
							}	
							
							if(is_array($arrOtherMaterial) && count($arrOtherMaterial)>0)
							{
								//usort($arrOtherMaterial, 'sortByOrder');
								usort($arrOtherMaterial, function($a, $b) {
									return $b['material_percentage'] <=> $a['material_percentage'];
								});
								
								foreach($arrOtherMaterial as $arrOM)
								{
									$materialCompositionContent.=$arrOM['material_percentage'].'% '.$arrOM['material_name'].' ('.$arrOM['material_code'].') + ';
								}
							}						
							$materialCompositionContent = rtrim($materialCompositionContent,' + ');							
						}
						
						$productContent.='<tr>
							<td class="productDetails" style="text-align:left;font-size:12px;padding-top:6px; " valign="middle">'.$vals['product'].' ('.$vals['prod_code'].')</td>
							<td class="productDetails" style="text-align:left;font-size:12px;padding-top:6px; " valign="middle">'.$vals['product_type'].' ('.$vals['prod_type_code'].')</td>
							<td class="productDetails" style="text-align:left;font-size:12px;padding-top:6px; " valign="middle">'.$materialCompositionContent.'</td>	';
							if(in_array('1',$standard_id)){		
								$productContent.='<td class="productDetails" style="text-align:left;font-size:12px;padding-top:6px; " valign="middle">'.$vals['got_product_code'].'</td>';
							}else{
								$productContent.='<td class="productDetails" style="text-align:left;font-size:12px;padding-top:6px; " valign="middle">'.$vals['product_code'].'</td>';
							};
							if(!in_array('1',$standard_id)){		
								$productContent.='<td class="productDetails" style="text-align:left;font-size:12px;padding-top:6px; " valign="middle">'.$vals['unit_id'].'</td>';
							};	
						'</tr>';
						$stropen =' (';
						$strclose = ')';
						if (!in_array($vals['product'].$stropen.$vals['prod_code'].$strclose, $arrProductCategories))
						{
							$arrProductCategories[]=$vals['product'].$stropen.$vals['prod_code'].$strclose;
						}

						//$arrCertificateCoveredProducts[]=array('name_of_product'=>$vals['product_type'],'material_composition'=>$vals['material_composition'],'label_grade'=>$vals['product_code']);
						
						$arrLabelGrade[$labelGradeCnt]=$vals['product_code'];
						$labelGradeCnt++;
					}
				}
																
				$arrCertificateLogo=array();
				$certificationStd='';
				$stdCodeArr =[];
				if(count($standard_id)>0){
					foreach($standard_id as $std_id){
						$stdmod = Standard::find()->where(['id'=>$std_id,'status'=>0])->one();
						if($stdmod!==null){
							$stdCodeArr[] = strtolower($stdmod->code);
							$arrCertificateLogo[] = strtolower($stdmod->code).'_logo.png';
						}
					}
				}


				// $standard_code_lower = strtolower($standardCode);
				// if($standard_code_lower=='gots'){
				// 	$certificationStd=$standard_code_lower;
				// 	$arrCertificateLogo[]=$standard_code_lower.'_logo.png';
				// //}elseif($standard_code_lower=='ocs'){
				// 	//$certificationStd=$standard_code_lower;
				// }elseif($standard_code_lower=='grs'){
				// 	$certificationStd=$standard_code_lower;
				// 	$arrCertificateLogo[]=$standard_code_lower.'_logo.png';
				// //}elseif($standard_code_lower=='rcs'){
				// 	//$certificationStd=$standard_code_lower;
				// }elseif($standard_code_lower=='ccs'){
				// 	$certificationStd=$standard_code_lower;
				// 	$arrCertificateLogo[]=$standard_code_lower.'_logo.png';
				// }elseif($standard_code_lower=='rds' || $standard_code_lower=='rws' || $standard_code_lower=='rms'){
				// 	$certificationStd=$standard_code_lower;
				// 	$arrCertificateLogo[]=$standard_code_lower.'_logo.png';				
				// }


				$cert_parent_app_id;
				$arrCertAuditors=array();
				$certAuditors='';
				

				// Getting the Auditor list,
				// Get the Audit by parent app id
				$application_audit_model = Audit::find()->where(['app_id' => $cert_parent_app_id])->one();
				$audit_model = AuditPlan::find()->where(['audit_id' => $cert_audit_id])->one();

				 if( $audit_model != null){
					$auditplanUnit=$audit_model->auditplanunit;
					foreach($auditplanUnit as $unit)
			   	    {
						 $unitauditors=$unit->unitauditors;
						if(count($unitauditors)>0)
						{
							foreach($unitauditors as $auditors)
							{
								$arrCertAuditors[]=$auditors->user->id;
							}
						}
					}
				}
			
				$certAuditors=implode(', ',$arrCertAuditors);
			 	
			// Signature Content
			$signature_content_dates='';

			if(in_array('gots',$stdCodeArr)){
			$signature_content_dates=''.$certificate_generate_date.'';
			}else {
			//$signature_content_dates=''.$certificate_generate_date.' <br>Last Updated: '.$last_updated_date.'';
			$signature_content_dates=''.$last_updated_date.' <br>Last Updated: '.$certificate_generate_date.'';
			}

			
				if(is_array($arrLabelGrade) && count($arrLabelGrade)>0)
				{
					foreach($arrLabelGrade as $lg){
						if(strpos($lg, 'blended') !== false || strpos(strtolower($lg), 'bl') !== false){
							$lgcode = strstr($lg,' ',true);
							$logocode = $lgcode.'_blended_logo.png';
							if(!in_array($logocode,$arrCertificateLogo)){
								$arrCertificateLogo[] = $logocode;
							}
						}elseif(strpos($lg, '100') !== false){
							$lgcode = strstr($lg,' ',true);
							$logocode = $lgcode.'_100_logo.png';
							if(!in_array($logocode,$arrCertificateLogo)){
								$arrCertificateLogo[] = $logocode;
							}
						}
					}
					// $resArray = array_filter($arrLabelGrade, function($value) {
					// 	return strpos($value, 'blended') !== false || strpos(strtolower($value), 'bl') !== false;
					// }); 										
					// if(is_array($resArray) && count($resArray)>0)
					// {
					// 	$arrCertificateLogo[]=$standard_code_lower.'_blended_logo.png';
					// }
					
					// $resArray = array_filter($arrLabelGrade, function($value) {
					// 	return strpos($value, '100') !== false;
					// }); 										
					// if(is_array($resArray) && count($resArray)>0)
					// {
					// 	$arrCertificateLogo[]=$standard_code_lower.'_100_logo.png';
					// }
				}	
				$arrProcess = array();
				$arrUnitWiseProcess = array();
				$arrUnitType=array('1'=>'Scope Holder','2'=>'Facility','3'=>'Sub Contractor');
				foreach($arrUnitType as $unitTypeKey=>$unitTypeVal)
				{
					$processQry = 'SELECT appunit.id as unit_id,prs.name as process_name,prs.code as process_code,appunit.unit_type as unit_type FROM `tbl_application_unit` AS appunit
					INNER JOIN `tbl_application_unit_process` AS appunitprocess ON appunitprocess.unit_id=appunit.id AND appunitprocess.standard_id IN ('.implode(',',$standard_id).') AND appunit.status=0 and appunitprocess.unit_process_status=0 and appunit.app_id='.$applicationID.' AND appunit.unit_type='.$unitTypeKey.'
					INNER JOIN `tbl_process` AS prs ON prs.id=appunitprocess.process_id ';
					$command = $connection->createCommand($processQry);
					$procesResult = $command->queryAll();			
					//group by appunitprocess.process_id
					if(count($procesResult)>0)
					{
						foreach($procesResult as $processRes)
						{
							//$arrProcess[$processRes['unit_type']][]=$processRes['process_name'];
							//$arrUnitWiseProcess[$processRes['unit_id']][]=$processRes['process_name'];

							if(!isset($arrProcess[$processRes['unit_type']]) || !in_array($processRes['process_name'],$arrProcess[$processRes['unit_type']])){
								$arrProcess[$processRes['unit_type']][]=$processRes['process_name'].$stropen.$processRes['process_code'].$strclose;
							}
							if(!isset($arrUnitWiseProcess[$processRes['unit_id']]) || !in_array($processRes['process_name'],$arrUnitWiseProcess[$processRes['unit_id']])){
								$arrUnitWiseProcess[$processRes['unit_id']][]=$processRes['process_name'].$stropen.$processRes['process_code'].$strclose;
							}
						}
					}
				}

				$subContractorORfacilityQry = 'SELECT appunit.unit_type as unit_type, 
				std.code as standard_code,appunit.id as unit_id,appunit.name as unit_name,appunit.address as unit_address,appunit.zipcode as unit_zipcode,appunit.city as unit_city,state.name as unit_state,country.name as unit_country FROM `tbl_application_unit` AS appunit
				INNER JOIN `tbl_application_unit_standard` AS appunitstd ON appunitstd.unit_id=appunit.id AND appunitstd.unit_standard_status=0 AND appunit.status=0 AND appunit.app_id='.$applicationID.' AND appunitstd.standard_id IN ('.implode(',',$standard_id).')
				INNER JOIN `tbl_state` AS state ON state.id=appunit.state_id
				INNER JOIN `tbl_standard` AS std ON std.id = appunitstd.standard_id
				INNER JOIN `tbl_country` AS country ON country.id=appunit.country_id
				GROUP BY appunit.id';	
				//AND appunit.unit_type!=1 
				$command = $connection->createCommand($subContractorORfacilityQry);
				$subContractResult = $command->queryAll();			
				
				$unitWiseSubContractorContent='';
				$unitWiseSubContractorContentSub = '';
				$unitWiseSubContractorContentCertified = '';
				$arrSubContractor=array();
				if(count($subContractResult)>0)
				{
					foreach($subContractResult as $subContractRes)
					{

						$subContractName = $subContractRes['unit_name'];
						$subContractUnitId = $subContractRes['unit_id'];

						
						$subContractAddress = $subContractRes['unit_address'].',<br> ';
						$subContractAddress .= $subContractRes['unit_city'].', '.$subContractRes['unit_zipcode'].',<br> ';
						$subContractAddress .= $subContractRes['unit_state'].',';
						$subContractAddress .= $subContractRes['unit_country'];	
						
						$unit_type = $subContractRes['unit_type'];
						$unitstandard = $subContractRes['standard_code'];					

						
						
						$unitProcess='NA';
						if(array_key_exists($subContractRes['unit_id'], $arrUnitWiseProcess))
						{
							$unitProcess=implode(',<br> ',$arrUnitWiseProcess[$subContractRes['unit_id']]);
						}
						if($unit_type ==1 || $unit_type ==2){
							$typename ='Facility';
							if($unit_type ==1){
								$typename = 'Main';							
							}

							if(!in_array('gots',$stdCodeArr)){
								 $faclity_name =$subContractName.'('.$subContractUnitId.')';
							}else{
								$faclity_name =$subContractName;
							}

							$unitWiseSubContractorContent.='<tr>
								<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px; " valign="middle">'.$faclity_name.'</td>
								<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px; " valign="middle">'.$subContractAddress.'</td>	
								<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px; " valign="middle">'.$unitProcess.'</td>';
								if(!in_array('gots',$stdCodeArr)){
									$unitWiseSubContractorContent.='<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px; " valign="middle">'.$unitstandard.'</td>';
								}'</tr>';
						     }
						
						if($unit_type ==3){
								//$standard_id
								$alreadyapplied = 0;
								$Standard = Standard::find()->where(['id'=>$standard_id])->one();
								if($Standard !==null){
									$standard_code = $Standard->code;
									$ReductionStandard = ReductionStandard::find()->where(['code'=>$standard_code])->one();
									if($ReductionStandard!== null){
										$ApplicationUnitCertifiedStandard = ApplicationUnitCertifiedStandard::find()->where(['standard_id'=>$ReductionStandard->id,'unit_id'=>$subContractRes['unit_id'] ])->one();
										if($ApplicationUnitCertifiedStandard !== null){
											$alreadyapplied = 1;
										}
									}
								}

								if(!in_array('gots',$stdCodeArr)){
									$subcontractor_name =$subContractName.'('.$subContractUnitId.')';
						 		  }else{
							  		 $subcontractor_name =$subContractName;
						  		 }
							 
								//ApplicationUnitCertifiedStandard::find()->where()->one();
								//
								if($alreadyapplied==0){
									$unitWiseSubContractorContentSub.='<tr>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$subcontractor_name.'</td>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$subContractAddress.'</td>	
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$unitProcess.'</td>';
										if($standard_code_lower!='gots'){
											$unitWiseSubContractorContentSub.='<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$unitstandard.'</td>	';
										}'</tr>
									</tr>';
								}else{
									$expiry_date = '';
									if($ApplicationUnitCertifiedStandard->expiry_date != ''){
										$expiry_date = date('Y-m-d',strtotime($ApplicationUnitCertifiedStandard->expiry_date));

										$certification_body = $ApplicationUnitCertifiedStandard->certificationbody?$ApplicationUnitCertifiedStandard->certificationbody->name:'-';
									   
									}
									
									if(in_array('gots',$stdCodeArr)){
										$unitWiseSubContractorContentCertified.='<tr>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$subcontractor_name.'</td>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$ApplicationUnitCertifiedStandard->license_number.'</td>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$expiry_date.'</td>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$subContractAddress.'</td>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$unitProcess.'</td>
									</tr>';
									}else if(!in_array('gots',$stdCodeArr)) {
									$unitWiseSubContractorContentCertified.='<tr>																		
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$subcontractor_name.' ('.$ApplicationUnitCertifiedStandard->license_number.')</td>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$certification_body.'</td>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$expiry_date.'</td>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$subContractAddress.'</td>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$unitProcess.'</td>
										<td class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;width:18px;" valign="middle">'.$unitstandard.'</td>	
									</tr>';
									}
								}
						}
						
						$arrSubContractor[]=array('name_of_operation'=>$subContractName,'address_of_operation'=>$subContractAddress,'processing_steps'=>$unitProcess);
							
					}
				}else{
					$unitWiseSubContractorContent.='<tr>
							<td colspan="2" style="text-align:center;padding:5px;" class="reportDetailLayoutInner">No Facility/Subcontractor found</td>
						</tr>';
				}
				
				$draftText='';
				if($model->status==$model->arrEnumStatus['certification_in_process'])
				{
					$draftText='DRAFT';
				}
				
				header('Access-Control-Allow-Origin: *');
				header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
				header('Access-Control-Max-Age: 1000');
				header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

				$html='';
				$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8','format' => 'A4',
					'margin_left' => 10,
					'margin_right' => 10,
					'margin_top' => 5,
					'margin_bottom' => 40,
					'margin_header' => 0,
					'margin_footer' => 3,
					'setAutoTopMargin' => 'stretch',
					'autoMarginPadding' => 0
				
				]);
				//$mpdf->SetDisplayMode('fullwidth');
				
				$qrCodeURL=Yii::$app->params['certificate_file_download_path'].'scan-certificate?code='.md5($model->code);
				
				if($draftText!='')
				{
					$mpdf->SetWatermarkText('DRAFT');
					$mpdf->showWatermarkText = true;
					$qrCodeURL=Yii::$app->params['qrcode_scan_url_for_draft'];				
				}
				
				//$mpdf->Image(Yii::$app->params['image_files'].'gcl-bg.jpg', 0, 0, 210, 297, 'jpg', '', true, false);
				//$mpdf->SetWatermarkImage(Yii::$app->params['image_files'].'gcl-bg.jpg',0.3,'P','P');
				//$mpdf->showWatermarkImage = true;
				
				
				//$mpdf->SetDefaultBodyCSS('background', "url('".Yii::$app->params['image_files'].'gcl-bg.jpg'."')");
				//$mpdf->SetDefaultBodyCSS('background-image-resize', 6);

				$qr = Yii::$app->get('qr');
				//Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;			
				//$qrCodeContent=$qr->setText($qrCodeURL)->writeDataUri();
				// $qrCodeContent=$qr->setText($qrCodeURL)			
				// ->setLogo(Yii::$app->params['image_files']."qr-code-logo.png")			
				// ->setLogoWidth(85)			
				// ->setEncoding('UTF-8')
				// ->writeDataUri();  	
				
				
				// Footer Content  // Accredited/Licensed by: IOAS Inc, Contract No: 125

				if(in_array('gots',$stdCodeArr)){
					$licensedbody = '
					Certification Body Accredited by: IOAS Inc ; Accreditation Number: 125 <br>
				';
				}else {
					$licensedbody = '
					Certification Body Licensed by: Textile Exchange ; Licensing Code: CB-GCL <br>
					Certification Body Accredited by: IOAS Inc ; Accreditation Number: 125 <br>
					Inspection Body:GCL INTERNATIONAL LTD
				';
				}

				$headerContent = '<div style="padding-top:15px;">
						<div style="width:30%;text-align: left;float:left;font-size:12px;">
							<img src="'.Yii::$app->params['image_files'].'header-img.png" border="0" style="width:136px;">						
						</div>
						
						<div style="width:20%;float:right;font-size:12px;font-family:Arial;">
							<img src="'.$qrCodeContent.'" style="width: 85px;margin-left: 45px;">
						</div>
					</div>';
				
				$signatureContent='<tr>
						<td style="text-align:left;font-size:12px;padding-top:5px;" valign="middle" class="reportDetailLayoutInner">Place and Date of Issue <br>London, '.$signature_content_dates.'</td>
						<td style="text-align:center;font-size:12px;padding-top:5px;" valign="middle" class="reportDetailLayoutInner">Certification Body</td>	
						<td style="text-align:center;font-size:12px;padding-top:5px;" valign="middle" class="reportDetailLayoutInner">Standard Logo</td>		
					</tr>
					<tr>
						<td style="text-align:left;font-size:12px;padding-top:5px;width:33%;" valign="middle" class="reportDetailLayoutInner">
							<img style="width:170px;" src="'.Yii::$app->params['image_files'].'certificate-sign.png" border="0">
							<br>Mahmut Sogukpinar
						</td>
						<td style="text-align:center;font-size:14px;padding-top:5px;width:33%;" valign="middle" class="reportDetailLayoutInner">
							<img style="width:100px;" src="'.Yii::$app->params['image_files'].'gcl-stamp.png" border="0">
						</td>
						<td style="text-align:center;font-size:14px;padding-top:5px;width:33%;" valign="middle" class="reportDetailLayoutInner">';
						if(is_array($arrCertificateLogo) && count($arrCertificateLogo)>0)
						{
							foreach($arrCertificateLogo as $certLogo)
							{
								$logoWidth='width:100px;';
								// if($standard_code_lower=='grs')
								// {
								// 	$logoWidth='width:170px;';
								// }elseif($standard_code_lower=='ccs'){
								// 	$logoWidth='width:190px;';
								// }
								
								$signatureContent.='<img style="'.$logoWidth.'" src="'.Yii::$app->params['image_files'].''.$certLogo.'" border="0">';									
							}
						}						
				$signatureContent.='</td>						
				</tr>';
				
				// $footerInnerContent1='This Certificate remains the property of GCL International Ltd. The Registration is subject to the Scheme Rules which are published at 
				// 	www.gcl-intl.com. Any misuse, alteration, forgery or falsification is an unlawful act. Please validate the authenticity of this certificate by visiting <span style="text-decoration: underline;">www.gcl-intl.com</span>';
					
				// $footerInnerContent2='<span style="font-weight:bold;font-size:12px;">GCL INTERNATIONAL LTD.</span><br>
				// 	Level 1, Devonshire House, One Mayfair Place, London, W1 J 8AJ, United Kingdom.';
					
				if(in_array('gots',$stdCodeArr)){
					$footerInnerContent3='This electronically issued document is the valid original version.<br><span style="text-align:left;font-size:11px;">License No. <span style="font-weight:bold;">'.$LicenseNo.'</span>';
				}else {
					$footerInnerContent3='<span style="text-align:left;font-size:11px;">License No. <span style="font-weight:bold;">'.$LicenseNo.'</span> </span>';
				}
				$footerInnerContent_domain='<span>To confirm this certificate, please scan the QR code located on the top right corner. The domain you see should be ":  <a style="color:black;" href="https://ssl.gcl-intl.com">https://ssl.gcl-intl.com</a>"</span>';
				
				$footerContentPageno='<td style="text-align:right;font-size:11px;" valign="middle"><span> Page {PAGENO} of {nbpg} </span><td>';
					
				$footerContent='<tr>
				<tr>
					<td style="text-align:right;font-size:11px;" valign="middle" class="reportDetailLayoutInner">		
					'.$footerInnerContent_domain.'
					</td>
				</tr>
				<tr>		
					<td style="font-size:11px;" valign="middle" class="reportDetailLayoutInner">		
					'.$footerInnerContent3.'
					</td>
				</tr>
				<tr>
					'.$footerContentPageno.'
				</tr>
				';

				$otherpage_header='
				<div  style="width:100%;text-align: center;float:center;font-size:12px;font-weight:bold;font-family:Arial;">
					<span style="font-size:10px;">GCL INTERNATIONAL LTD</span><br>
					<span style="font-size:10px;">Level 1, Devonshire House, One Mayfair Place, London, W1 J 8AJ, United Kingdom.</span><br><br>
					<span style="font-size:14px;">Scope Certificate Number '.$certificateNumber.' (continued)</span><br>
					<span>'.$companyName.'</span><br>';
					if(in_array('gots',$stdCodeArr)){
						$otherpage_header.='<span>'.$standardCode.' Version '.$standardVersion.'</span>';
					} else {
                     	$otherpage_header.='<span>'.implode(',',$te_standardNameWithVersions).'</span>';
					}
					'</div>
					<br>
				';

				// Product Categories And Unit's as per the gots and TE format changes

				if(in_array('gots',$stdCodeArr)){
				$productcontent_thead='<thead>
				<tr>
		            <td valign="middle" class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;font-weight:bold;">Product Category</td>
					<td style="text-align:left;font-size:12px;padding-top:6px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Product Details</td>
					<td style="text-align:left;font-size:12px;padding-top:6px;font-weight:bold;" valign="middle" class="productDetails">Material Composition*</td>	
					<td style="text-align:left;font-size:12px;padding-top:6px;font-weight:bold;width:15%;" valign="middle" class="productDetails">Lable Grade</td>
				</tr>
				</thead>';

				$unitcontent='
					<thead>
						<tr>
							<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Facility Name</td>
							<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Address</td>	
							<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Process Categories</td>	
						</tr>
					</thead>';
					$unitSubContractor='
			   	<thead>	
						<tr>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Subcontractor Name <br>(Facility Name)</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Address</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Process Categories</td>	
					</tr>
				</thead>';

				$unitCertifiedSubContractor='
				<thead>
					<tr>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Subcontractor Name <br> (Facility Name)</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Licence Number</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Expiry Date</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Address</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Process Categories</td>
					</tr>							
				</thead>
				';
			   }
			   else {
				$productcontent_thead='<thead>
				<tr>
					<td valign="middle" class="productDetails" style="text-align:left;font-size:12px;padding-top:5px;font-weight:bold;">Product Category</td>
					<td style="text-align:left;font-size:12px;padding-top:6px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Product Details</td>
					<td style="text-align:left;font-size:12px;padding-top:6px;font-weight:bold;" valign="middle" class="productDetails">Material Composition*</td>	
					<td style="text-align:left;font-size:12px;padding-top:6px;font-weight:bold;width:15%;" valign="middle" class="productDetails">Standard (Label Grade) </td>
					<td style="text-align:left;font-size:12px;padding-top:6px;font-weight:bold;width:15%;" valign="middle" class="productDetails">Facility Number</td>
				</tr>
				</thead>';

				$unitcontent='
				<thead>
					<tr>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">Facility Name  - <br>Number</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">Address</td>	
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">Process Categories</td>	
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">Standards</td>	
					</tr>
				</thead>';

				$unitSubContractor='
				<thead>	
					 <tr>
					 	<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Subcontractor Name <br> (Facility Name - Number)</td>
					 	<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Address</td>
					 	<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Process Categories</td>	
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Standards</td>								
					 </tr>
				</thead>';

				$unitCertifiedSubContractor='
				<thead>
					<tr>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Subcontractor Name-Number <br> (License Number)</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Certification Body</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Expiry Date</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Address</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Process Categories</td>
						<td style="text-align:left;font-size:13px;padding-top:5px;font-weight:bold;width:18%;" valign="middle" class="productDetails">Standards</td>	
					</tr>							
				</thead>
				';

				}
				

				// Site Label Header 

				if(in_array('gots',$stdCodeArr)){
					$scopeholder_label="Facility Appendix";
					$subcontractor_label="Non-Certified Subcontractor Appendix ";
					$scopeholder_label_appendix = "Under the scope of this certificate, the following facilities have been audited and found to be in conformity with the Standard.";
				}
				else {
					$scopeholder_label="Site Appendix";
					$subcontractor_label="Associated Subcontractor Appendix";
					$scopeholder_label_appendix = "Under the scope of this certificate, the following facilities have been audited and found to be in conformity.";
				}

			
				$html='
				<style>
				table {
					border-collapse: collapse;
				}		
				div.chapter2 {
					page-break-before: right;
					page: chapter2;
					odd-header-name: html_secondpagesheader;
        			even-header-name: html_secondpagesheader;
				}				
								
				div.chapter1 {
					page-break-before: always;
					page: chapter1;
					odd-header-name: html_lastpagesheader;
        			even-header-name: html_lastpagesheader;
				}
									
				
				@page {  
					margin-top: 8%;
					margin-bottom: 22%;
					header: html_otherpageheader;
					footer: html_otherpagesfooter;
					background: url('.Yii::$app->params["image_files"].'gcl-bg-1.png) repeat 0 0;
					background-image-resize: 0;
					margin-top: 3cm;
				}

				@page :first {
					margin-bottom: 10%!important;    
					header: html_firstpageheader;
					footer: html_firstpagefooter;
					margin-top: 2cm;
				}								
				
				table, tbody, tr, th, td{
					background-color: rgba(0, 0, 0, 0.0) !important;
				}
				table.reportDetailLayout {
					border-collapse: collapse;
					width:100%;
					font-size:12px;
					font-family:Arial;
					text-align: left;
					margin-bottom:5px;
					margin-top:5px;
				}

				table.productDetails {
					border-collapse: collapse;
					border: 1px solid #000000;
					width:100%;
					font-size:12px;
					font-family:Arial;
					text-align: left;
					margin-bottom:5px;
					margin-top:5px;
				}

				td.productDetails {
					text-align: center;
					border: 1px solid #000000;
					font-size:12px;
					font-family:Arial;
					text-align: left;
					/*background-color:#DFE8F6;*/
					padding:3px;
				}

				td.reportDetailLayout {
					text-align: center;
					font-size:12px;
					font-family:Arial;
					text-align: left;
					/*background-color:#DFE8F6;*/
					padding:3px;
				}
				td.reportDetailLayoutHead {
					text-align: center;
					font-size:12px;
					font-family:Arial;
					text-align: left;
					/*background-color:#4e85c8;*/
					/* background-color:#006fc0;*/
					padding:3px;
					color:#FFFFFF;
				}

				td.reportDetailLayoutInner {
					text-align: center;
					font-size:12px;
					font-family:Arial;
					text-align: left;
					 
					padding:3px;
				}
				</style>
				
				<htmlpageheader name="firstpageheader" style="display:none">
					'.$headerContent.'	
				</htmlpageheader>

				<htmlpagefooter name="firstpagefooter" style="display:none">
					<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayout">
						'.$footerContent.'													
					</table>
				</htmlpagefooter>

				<htmlpageheader name="otherpageheader" style="display:none">
					'.$headerContent.'
					'.$otherpage_header.'
				</htmlpageheader>
				
				<htmlpageheader name="secondpagesheader" style="display:none;">
					'.$headerContent.'
					'.$otherpage_header.'
				</htmlpageheader>
				
				<htmlpageheader name="lastpagesheader" style="display:none">
					'.$headerContent.'
					'.$otherpage_header.'					
				</htmlpageheader>
				
				<htmlpagefooter name="otherpagesfooter" style="display:none">
				<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayout">
					'.$signatureContent.'
					</table>
					<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayout">					
						<tr>		
							<td style="font-size:11px;" colspan="2" valign="middle" class="reportDetailLayoutInner">		
							'.$footerContent.'
							</td>
						</tr>						
					</table>					
				</htmlpagefooter>
				
				
				
				<htmlpagefooter name="secondpagesfooter" style="display:none">
					<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayout">
						'.$signatureContent.'
					</table>
					<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayout">
						'.$footerContent.'
					</table>
				</htmlpagefooter>
				
				<htmlpagefooter name="lastpagesfooter" style="display:none">
				<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayout">
				'.$signatureContent.'
			</table>
					<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayout">
						'.$footerContent.'
					</table>
				</htmlpagefooter>
				
				<htmlpagefooter name="innerpagesfooter" style="display:none">
					<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayout">
						<tr>
							<td style="text-align:center;font-size:14px;padding-top:5px;width:33%;" valign="middle" class="reportDetailLayoutInner">
							<img style="width:100px;" src="'.Yii::$app->params['image_files'].'gcl-stamp.png" border="0">							
							</td>
						</tr>
					</table>					
				</htmlpagefooter>
				
											
			
					<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayout">
						
						<tr>
							<td style="text-align:center;font-weight:bold;font-family:Arial;" valign="middle" class="reportDetailLayoutInner">
							<span style="font-size:10px;">GCL INTERNATIONAL LTD</span><br>
							<span style="font-size:10px;">Level 1, Devonshire House, One Mayfair Place, London, W1 J 8AJ, United Kingdom.</span>
							</td>					  
						</tr>

						<tr>
							<td style="text-align:center;font-weight:bold;padding-top:10px" valign="middle" class="reportDetailLayoutInner">
							<span style="font-size:24px;">Scope Certificate</span><br>
							<span style="font-size:16px;">Scope Certificate Number '.$certificateNumber.'</span>
							</td>					  
						</tr>

						<tr>
							<td style="text-align:center;font-size:14x;padding-top:12px;" valign="middle" class="reportDetailLayoutInner">GCL INTERNATIONAL LTD<br>certifies that</td>	  
						</tr>
						
						<tr>
							<td style="text-align:center;font-weight:bold;font-size:13px;padding-top:12px;" valign="middle" class="reportDetailLayoutInner">
							<span style="font-size:15px;">'.$companyName.'</span><br>
							<span style="font-size:13px;">License Number '.$LicenseNo.'</span><br>
							'.$companyAddress.'<br>'.$companyAddress_town_postcode.'<br>'.$companyAddress_state_country.'
							</td>	  						
						</tr>
						
						<tr>
							<td style="text-align:center;font-size:12px;padding-top:8px;" valign="middle" class="reportDetailLayoutInner">has been audited and found to be in conformity with the</td>	 
						</tr>
						
						<tr>';
						if(in_array('gots',$stdCodeArr)){
							$html.='<td style="text-align:center;font-size:18px;padding-top:12px;font-weight:bold;" valign="middle" class="reportDetailLayoutInner">'.strtoupper($standardName).' ('.strtoupper($standardCode).')'.'Version '.$standardVersion.'</td>';
						}
						else {
							$html.='<td style="text-align:center;font-size:18px;padding-top:12px;font-weight:bold;" valign="middle" class="reportDetailLayoutInner">'.implode(',',$te_standardNameWithVersions).'</td>';
						}	  
						$html.='</tr>
						</tr>
						
						<tr>';
						if(in_array('gots',$stdCodeArr)){
							$html.='<td style="text-align:left;font-size:12px;padding-top:12px;margin-bottom:0px;" valign="middle" class="reportDetailLayoutInner">Product categories as mentioned below (and further specified in the product appendix) conform with this standard:</td>';
						}else{
							$html.='<td style="text-align:left;font-size:12px;padding-top:12px;margin-bottom:0px;" valign="middle" class="reportDetailLayoutInner">Product categories mentioned below (and further specified in the product appendix) conform with the standard(s):</td>';
						}
						$html.='</tr>	
						<tr>
							<td style="text-align:center;font-size:12px;margin-top:0px;" valign="middle" class="reportDetailLayoutInner">
							<b>'.implode('; ',$arrProductCategories).'</b>
							</td>	  
						</tr>
						<tr>';
						if(!in_array('gots',$stdCodeArr)){
							$html.='<td style="text-align:left;font-size:12px;padding-top:12px;margin-bottom:0px;" valign="middle" class="reportDetailLayoutInner">Process categories carried out under responsibility of the above mentioned company for the certified products cover:</td>';
						}else{
							$html.='<td style="text-align:left;font-size:12px;padding-top:12px;margin-bottom:0px;" valign="middle" class="reportDetailLayoutInner">Process categories carried out under responsibility of the above mentioned organization for the certified products cover:</td>';
						}
						$html.='/tr>					
						<tr>
							<td style="text-align:center;font-size:12px;font-weight:bold;" valign="middle" class="reportDetailLayoutInner">';
							
							$arrCertificateScopeHolderFacilityProcess=array();	
                            if(array_key_exists('1', $arrProcess) ) {						
							$arrCertificateScopeHolderFacilityProcess=$arrProcess['1'];							
							if(array_key_exists('2', $arrProcess) && is_array($arrProcess['2']) && count($arrProcess['2'])>0)
							{
								$arrCertificateScopeHolderFacilityProcess=array_merge($arrCertificateScopeHolderFacilityProcess,$arrProcess['2']);								
							}
							$arrCertificateScopeHolderFacilityProcess = array_unique($arrCertificateScopeHolderFacilityProcess);
							$html.=implode('; ', $arrCertificateScopeHolderFacilityProcess);
							
							$arrCertificateSubContractProcess=array();														
							if(array_key_exists('3', $arrProcess) && is_array($arrProcess['3']) && count($arrProcess['3'])>0)
							{
								$arrCertificateSubContractProcess = $arrProcess['3'];
								$arrCertificateSubContractProcess = array_unique($arrCertificateSubContractProcess);								
								$html.=','.implode('*;', $arrCertificateSubContractProcess).'*;';
							}
							$html.='<br><span style="text-align:left;font-size:12px;font-weight:normal;">*The processes marked with an asterisk may be carried out by subcontractors.</span>';
                        }
							$html.='</td>	  
						</tr>
						<tr>
							<td style="text-align:left;font-size:12px;padding-top:13px;" valign="middle" class="reportDetailLayoutInner">This Certificate is valid until: <b>'.$certificate_expiry_date.'</b></td>	  
						</tr>
						<tr>';
						if(!in_array('gots',$stdCodeArr)){
							$html.='<td style="text-align:left;font-size:12px;padding-top:2px;" valign="middle" class="reportDetailLayoutInner">Audit criteria: '.implode(',',$te_auditCriteriaStandardVersion).' ; Content Claim Standard '.$ccs_version.' ; Textile Exchange Standards Claims Policy '.$te_standard_version.' <b></b></td>';	
						}  
						$html.='</tr>
					</table>

					<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayout" style="padding-top:5px;">
					'.$signatureContent.'						
					</table>
					
					<table cellpadding="0" cellspacing="0" border="0" width="100%" class="reportDetailLayout">
					    <tr>
							<td colspan="2" style="text-align:left;font-size:12px;padding-top:10px;" valign="middle" class="reportDetailLayoutInner">
							'.$licensedbody.'		
							</td>
						</tr>					
						<tr>
							<td colspan="2" style="text-align:left;font-size:12px;padding-top:10px;" valign="middle" class="reportDetailLayoutInner">
								This Scope Certificates provides no proof that any goods delivered by its holder are '.$standardCode.' certified. Proof of '.$standardCode.' certification of goods delivered is provided by a valid Transaction Certificate (TC) covering them. 								
							</td>
						</tr>
						
						<tr>';
						if(!in_array('gots',$stdCodeArr)){
							$html.='
							<td colspan="2" style="text-align:left;font-size:12px;padding-top:10px;" valign="middle" class="reportDetailLayoutInner">
							The issuing body may withdraw this certificate before it expires if the declared conformity is no longer guaranteed.<br>
							<span>To authenticate this certificate, please visit <a style="color:#000000" href="www.TextileExchange.org/Certificates">www.TextileExchange.org/Certificates.</a></span>
							</td>
							';	
						}else {
							$html.='<td colspan="2" style="text-align:left;font-size:12px;padding-top:10px;" valign="middle" class="reportDetailLayoutInner">
							The issuing body may withdraw this certificate before it expires if the declared conformity is no longer guaranteed.<br><br>
							<span>For directions on how to authenticate this certificate, please visit GOTS web page Approved Certification Bodies</span>
							</td>
							';
						}  
						$html.='</tr>
						</tr>
					</table>	
										
					<pagebreak />
					
                    <div>

                    <table cellpadding="0" cellspacing="0" border="0" class="reportDetailLayout">
						<tr><td style="padding-top:8px;"><span style="text-align:left;font-size:12px;font-family:Arial;">Under the scope of this certificate, the following products are covered.</span></td></tr>				
						<tr><td><span style="font-weight:bold;text-align:left;font-size:13px;font-family:Arial;"><br>Products Appendix</span></td></tr>
					</table>

                    <table cellpadding="0" cellspacing="0" border="0" width="100%" class="productDetails" style="margin-top:5cm;">	';	

                    $html.=$productcontent_thead;						
						
					$html.=$productContent;	
					$html.='</table>
                    </div>
                    <pagebreak />

					<div style="padding-top:10px;">
			
					<table cellpadding="0" cellspacing="0" border="0" class="reportDetailLayout">
						
					<tr><td><span style="text-align:left;font-size:12px;font-family:Arial;">'.$scopeholder_label_appendix.'</span></td></tr>				
						
					<tr><td><span style="font-weight:bold;text-align:left;font-size:13px;font-family:Arial;"><br>'.$scopeholder_label.'</span></td></tr>
						
					</table>

					<table cellpadding="0" cellspacing="0" border="0" width="100%" class="productDetails" style="margin-top:15px">';
					$html.=$unitcontent;
					$html.=$unitWiseSubContractorContent;
						
					if($unitWiseSubContractorContent==''){
						$html.= '<tr>
							<td style="text-align:center;font-size:13px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>
							<td style="text-align:center;font-size:13px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>	
							<td style="text-align:center;font-size:13px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>	
							<td style="text-align:center;font-size:13px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>	
						</tr>';
						}
										
					$html.='</table><sethtmlpageheader name="secondpagesheader" value="on"  page="ALL" show-this-page="1" />
						<div style="text-align:left;font-size:13px;padding-top:15px;font-weight:bold;padding-top:15px;padding-bottom:10px;">'.$subcontractor_label.'</div>
						<table cellpadding="0" cellspacing="0" border="0" width="100%" class="productDetails">';
						$html.=$unitSubContractor;
						$html.=$unitWiseSubContractorContentSub;
						if($unitWiseSubContractorContentSub==''){
							$html.= '<tr>
								<td style="text-align:center;font-size:13px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>
								<td style="text-align:center;font-size:13px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>
								<td style="text-align:center;font-size:13px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>
																
							</tr>';
						}
					$html.='</table><sethtmlpageheader name="secondpagesheader" value="on"  page="ALL" show-this-page="1" />
					
					<div style="text-align:left;font-size:13px;padding-top:15px;font-weight:bold;padding-bottom:10px;">Independently Certified Subcontractor Appendix</div>
							
						<table cellpadding="0" cellspacing="0" border="0" width="100%" class="productDetails">';
						$html.=$unitCertifiedSubContractor;
						$html.=$unitWiseSubContractorContentCertified;	
						if($unitWiseSubContractorContentCertified==''){
							$html.= '<tr>
								<td style="text-align:center;font-size:14px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>
								<td style="text-align:center;font-size:14px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>
								<td style="text-align:center;font-size:14px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>
								<td style="text-align:center;font-size:14px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>
								<td style="text-align:center;font-size:14px;padding-top:5px;font-weight:bold;" valign="middle" class="productDetails">-</td>
							</tr>';
						}
					$html.='</table><sethtmlpageheader name="secondpagesheader" value="on"  page="ALL" show-this-page="1" />';
				
				     $html.='<sethtmlpagefooter name="secondpagesfooter" value="1" />';
				
				$html.= $this->getGotsContent();
				
				$html.='</div><sethtmlpageheader name="secondpagesheader" value="on"  page="ALL" show-this-page="1" />';
				$mpdf->SetProtection(array('copy','print'), '', 'PeriyaRagasiyam');						
				$mpdf->WriteHTML($html);
				
				$pdfName = $customeroffernumber.'_'.$standardCode.'_CERTIFICATE_' . date('YmdHis') . '.pdf';
				$filePath = Yii::$app->params['certificate_files'].$pdfName;
				if($returnType)
				{										
					$mpdf->Output($filePath,'F');
					
					$model->filename=$pdfName;
					$model->save();					
				}else{
					$mpdf->Output($pdfName,'D');					
				}			
			}											
		}		
	}


	private function getGotsContent(){
		$gotcontent = '';

		return $gotcontent;
	}


	public function applicationStandardDecline($pdata){
		$standard_ids = $pdata['standard_id'];
		$app_id = $pdata['app_id'];
		$change_status = isset($pdata['status'])?$pdata['status']:1;


		//$parent_app_id = $pdata['parent_app_id'];
		$model = Application::find()->where(['id' => $app_id])->one();
		if($model !== null){

			$appProduct=$model->applicationproduct;
			foreach($standard_ids as $standard_id ){
				if(count($appProduct)>0)
				{
					foreach($appProduct as $prd)
					{
						if(count($prd->productstandard)>0){
							foreach($prd->productstandard as $productstandard)
							{
								if($productstandard->standard_id == $standard_id){
									$productstandard->product_standard_status = 1;
									$productstandard->save();
								}
							}
						}
					}
				}

				$modelappstd = ApplicationStandard::find()->where(['app_id' => $app_id,'standard_id'=>$standard_id ])->one();
				if($modelappstd !== null){
					$modelappstd->standard_status = $change_status;
					if($modelappstd->save()){

						if(count($model->applicationunit)>0){
							foreach($model->applicationunit as $unit){
								$standardapplicable = 0;
								$unitappstandard=$unit->unitappstandard;
								if(count($unitappstandard)>0)
								{
									foreach($unitappstandard as $unitstd)
									{
										if($unitstd->standard_id == $standard_id){
											$standardapplicable = 1;
											$unitstd->unit_standard_status = $change_status;
											$unitstd->save();
										}
									}
								}
								if($standardapplicable == 0){
									continue;
								}

								$unitprocess=$unit->unitprocessall;
								if(count($unitprocess)>0)
								{										
									foreach($unitprocess as $unitPcs)
									{
										if($unitPcs->standard_id == $standard_id){
											$unitPcs->unit_process_status= $change_status;
											$unitPcs->save(); 
										}
									}									
								}


								$unitbsector=$unit->unitbusinesssector;
								if(count($unitbsector)>0)
								{									
									foreach($unitbsector as $unitbs)
									{

										$changeBsector = 1;
										$commonStd = [$standard_id];
										//For change of address error 
										if(count($commonStd)>0){
											$business_sector_id = $unitbs->business_sector_id;
											$chkBusiness = ['business_sector_id'=>$business_sector_id,'standard_id'=>$commonStd];
											$relatedsector = Yii::$app->globalfuns->checkBusinessSectorInStandard($chkBusiness);
											if(!$relatedsector){
												$changeBsector = 0;
											}else{
												if(count($unitappstandard)>1){
													$changeBsector = 0;
												}
											}
										}
										
										if($changeBsector){
											$unitbs->unit_business_sector_status = $change_status;
											$unitbs->save(); 
										}
										


										
										$unitbsectorgp=$unitbs->unitbusinesssectorgroup;
										if(count($unitbsectorgp)>0)
										{									
											foreach($unitbsectorgp as $unitbsgp)
											{
												if(count($commonStd)>0){
													$business_sector_group_id = $unitbsgp->business_sector_group_id;
													$chkBusiness = ['business_sector_group_id'=>$business_sector_group_id,'standard_id'=>$commonStd];
													$relatedsector = Yii::$app->globalfuns->checkBusinessSectorGroupInStandard($chkBusiness);
													if(!$relatedsector){
														continue;
													}
												}
												
												$unitbsgp->unit_business_sector_group_status = $change_status;
												$unitbsgp->save(); 
											}
										}
									
									}
								}

							}
						}
					}
				}


			}
			

			
		}
		return true;
	}
}
