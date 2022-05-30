<?php
namespace app\modules\report\controllers;

use Yii;

use app\modules\application\models\Application;
use app\modules\application\models\ApplicationStandard;
use app\modules\application\models\ApplicationChangeAddress;
use app\modules\master\models\User;
use app\modules\transfercertificate\models\Request;
use app\modules\audit\models\Audit;

use yii\web\NotFoundHttpException;

use sizeg\jwt\Jwt;
use sizeg\jwt\JwtHttpBearerAuth;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * TcReportController implements the CRUD actions for Product model.
 */
class TcReportController extends \yii\rest\Controller
{
	private $styleWhite = array('font'  => array('name'  => 'Arial','color' => array('rgb' => 'FFFFFF'),'bold'  => true,'size'  => 10,));
	private $styleBgColor = array('fill' => array('type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'color' => array('rgb' => '578CDE')));
	private $styleCenter = array('alignment' => array('horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,));
	private $styleLeft = array('alignment' => array('horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT));
	private $styleVCenter = array('alignment' => array('vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER));
	private $styleHigh = array('font'  => array('color' => array('rgb' => 'FF0000'),'bold'  => true,));
	private $styleMedium = array('font'  => array('color' => array('rgb' => 'F79647'),'bold'  => true,));
	private $styleLow = array('font'  => array('color' => array('rgb' => '00B050'),'bold'  => true,));
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
		if(!Yii::$app->userrole->hasRights(array('tc_report')))
		{
			return false;
		}
		
		$responsedata =array('status'=>0,'message'=>"No Data Found");
		$post = yii::$app->request->post();
		$connection = Yii::$app->getDb();
		$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();
		
		$modelObj = new Request();	
	
		$userData = Yii::$app->userdata->getData();
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		$userid=$userData['userid'];
		$user_type=$userData['user_type'];
		$role=$userData['role'];
		$rules=$userData['rules'];
		$franchiseid=$userData['franchiseid'];
		$is_headquarters =$userData['is_headquarters'];
		
		$resource_access=$userData['resource_access'];				
		$model = Request::find()->where(['t.status'=> $modelObj->arrEnumStatus['approved']])->alias('t');			
		$model = $model->join('inner join', 'tbl_application as app','t.app_id=app.id');
		$model = $model->join('inner join', 'tbl_application_change_address as appaddress','appaddress.id=app.address_id');
		$model = $model->join('inner join', 'tbl_tc_request_standard as tc_standard','tc_standard.tc_request_id=t.id');
		
		if(isset($post['standard_id']) && is_array($post['standard_id']) && count($post['standard_id'])>0)
		{
			$model = $model->andWhere(['tc_standard.standard_id'=> $post['standard_id']]);
		}
		
		if(isset($post['oss_id']) && is_array($post['oss_id']) && count($post['oss_id'])>0)
		{
			$model = $model->andWhere(['app.franchise_id'=> $post['oss_id']]);				
		}
		else
		{
			if($is_headquarters != 1)
			{
				$model = $model->andWhere(['app.franchise_id'=> $franchiseid]);	
			}
		}

		if(isset($post['app_id']) && $post['app_id'] !='')
		{
			$model = $model->andWhere(['t.app_id'=> $post['app_id']]);				
		}
	
		if(isset($post['from_date']) && $post['from_date'] !='' && isset($post['to_date']) && $post['to_date'] !='')
		{
			$model = $model->join('inner join', 'tbl_tc_request_reviewer_comment as reviewerc','reviewerc.tc_request_id=t.id and reviewerc.status=1 ');

			$model = $model->andWhere(['>=','reviewerc.created_at', strtotime($post['from_date'])]);				
			$model = $model->andWhere(['<=','reviewerc.created_at', strtotime($post['to_date'])]);
		}		
		$model = $model->groupBy(['t.id']);
		
		$app_list=array();
		$model = $model->all();		
		if(count($model)>0)
		{
			if($post['type']!='submit')
			{
				$arrHeaderLabel=array('TC Ref. No.','Issue Date','Operator ID','Name of Operator','Sub-program','Product Name','Buyer Name','Buyer Address','Country of Destination','Seller Name and Address','Invoice Number','Gross Weight','Net Weight','Certified Weight','Supplier TC Number','Supplier Name','Supplier Product Name','Responsible OSS (Country)','Approved By');
				$styleWhite = array('font'  => array('name'  => 'Arial','color' => array('rgb' => 'FFFFFF'),'bold'  => true,'size'  => 10,));
				$styleBgColor = array('fill' => array('type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'color' => array('rgb' => '578CDE')));
				$styleCenter = array('alignment' => array('horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,));
				$styleLeft = array('alignment' => array('horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT));
				$styleVCenter = array('alignment' => array('vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER));
				$styleHigh = array('font'  => array('color' => array('rgb' => 'FF0000'),'bold'  => true,));
				$styleMedium = array('font'  => array('color' => array('rgb' => 'F79647'),'bold'  => true,));
				$styleLow = array('font'  => array('color' => array('rgb' => '00B050'),'bold'  => true,));
				
				$spreadsheet = new Spreadsheet();
				$sheet = $spreadsheet->getActiveSheet();
				
				$column = 'A';
				foreach($arrHeaderLabel as $headerKey=>$headerLabel)
				{
					$sheet->setCellValue($column.'1', $headerLabel);
					$defaultWidth=25;
					if($column=='C' || $column=='L' || $column=='M' || $column=='N' ){
						$defaultWidth=10;
					}elseif($column=='B' || $column=='O'){
						$defaultWidth=20;
					}elseif($column=='D'){
						$defaultWidth=35;
					}
					$sheet->getColumnDimension($column)->setWidth($defaultWidth);
					$column++;
				}			
				
				

				$i=2;
			}	
			
			foreach($model as $offer)
			{
				$data=array();				
				$application = $offer->application;
				$pdtbuyer = $offer->buyer;
				$data['company_name']=($application)?$application->companyname:'';
				$data['customer_number']=($application)?$application->customer->customer_number:'';
				$data['buyer_name']=($offer->buyer_id)?$offer->buyer->name:'';
				$data['buyer_address']=($offer->buyer_id)?$pdtbuyer->address.', '.$pdtbuyer->city.', '.($pdtbuyer->state?$pdtbuyer->state->name.', ':'').$pdtbuyer->country->name.' - '.$pdtbuyer->zipcode:'';

				//.', '.$consigneedata->city.', '.($consigneedata->state?$consigneedata->state->name.', ':'').$consigneedata->country->name.' - '.$consigneedata->zipcode,
				$applicationModelObject = $application->applicationaddress;
				$data['seller_name'] = $applicationModelObject->company_name ;
				$data['seller_address'] = $applicationModelObject->address.', '.$applicationModelObject->city.', '.$applicationModelObject->state->name.', '.$applicationModelObject->country->name.' - '.$applicationModelObject->zipcode;
			


				//$data['seller_name_address']=($offer->seller_id)?$offer->seller->name.", ".$offer->seller->address:'';
				$data['gross_weight']=$offer->total_gross_weight;
				$data['net_weight']=$offer->total_net_weight;
				$data['certified_weight']=$offer->total_certified_weight;
				//$data['oss']=($application)?$application->franchise->usercompanyinfo->osp_details." (".$application->franchise->usercompanyinfo->companycountry->name.")":'';
				
				$data['oss']='';
				if($application)
				{
					$usercompanyinfoObj = $application->franchise->usercompanyinfo;
					$data['oss']=$usercompanyinfoObj ? 'OSS '.$usercompanyinfoObj->osp_number.' - '.$usercompanyinfoObj->companycountry->name:'';
				}
				
				$data['approved_by']=$offer->reviewer->user?$offer->reviewer->user->first_name.' '.$offer->reviewer->user->last_name:'';
				$data['approved_date']=$offer->currentreviewercmt?date($date_format,$offer->currentreviewercmt->created_at):'';
				//$data['sub_program']= $offer->standard->code;

				$data['standard_name']='';
				$RegistrationNoArray = [];
				$tc_standard=$offer->standard;
				if(count($tc_standard)>0)
				{
					$standard_names='';
					$app_standard=[];
					$ospnumber = $application->franchise->usercompanyinfo->osp_number;
					$customeroffernumber = $application->customer->customer_number;
					if($offer->status!=$offer->arrEnumStatus['approved'])
					{
						$TransactionCertificateNo=$offer->id;
					}else{
						$TransactionCertificateNo=$offer->tc_number;
					}

					foreach($tc_standard as $std)
					{
						$app_standard[]=$std->standard->code;
						//$std_licence_number.= $std->standard->license_number."<br>";
						$standardCode = $std->standard->code;
						$RegistrationNoArray[] = "GCL-".$customeroffernumber.'/'.$ospnumber.$standardCode.'-'.$TransactionCertificateNo;
					}
					$standard_names=implode(',',$app_standard);
					$data['standard_name']=$standard_names;
				}
				$data['tc_ref_no']= implode(', ',$RegistrationNoArray);
				//$offer->getAppProductsByStandard();
				$data['product_data']=[];
				if(count($offer->product)>0){
					foreach($offer->product as $tcproduct){
						$productdata = Yii::$app->globalfuns->getAppProductsByStandard($tcproduct->product_id);
						$consigneedata = $tcproduct->consignee;
						$requestproductinputmaterial = $tcproduct->requestproductinputmaterial;
						//$supplierdata = [];
						$supplierdata['supplier_name'] = '';
						$supplierdata['product_name'] = '';
						$supplierdata['tc_number'] = '';
						if(count($requestproductinputmaterial)>0){
							foreach($requestproductinputmaterial as $inputmaterial){
								$rawmaterialdata = $inputmaterial->rawmaterial;
								
								$supplierdata['supplier_name'] = $supplierdata['supplier_name'].$rawmaterialdata->supplier_name."\n\n";
								$supplierdata['product_name'] = $inputmaterial->rawmaterialproduct?$supplierdata['product_name'].$inputmaterial->rawmaterialproduct->product_name."\n\n":"";
								if($rawmaterialdata->tc_number != ''){
									$supplierdata['tc_number'] = $supplierdata['tc_number'].$rawmaterialdata->tc_number."\n\n";
								}
							}
						}
						$supplierdata['tc_number'] = rtrim($supplierdata['tc_number'],"\n\n");
						$supplierdata['product_name'] = rtrim($supplierdata['product_name'],"\n\n");
						$supplierdata['supplier_name'] = rtrim($supplierdata['supplier_name'],"\n\n");

						$data['product_excel_list'] = $productdata['product_excel_list'];
						$data['product_data'][]=[
							'product_name' => $data['product_excel_list'],
							'buyer_name' => $consigneedata->name,
							'buyer_address' => $consigneedata->address.', '.$consigneedata->city.', '.($consigneedata->state?$consigneedata->state->name.', ':'').$consigneedata->country->name.' - '.$consigneedata->zipcode,
							'country_of_destination' => $consigneedata->country->name,
							'invoice_no' => $tcproduct->invoice_no,
							'gross_weight' => $tcproduct->gross_weight,
							'net_weight' => $tcproduct->net_weight,
							'certified_weight' => $tcproduct->certified_weight,
							'supplier_data' => $supplierdata
						];
					}
				}
				if($post['type']=='submit')
				{
					$app_list[]=$data;
				}else{									
					
					$column = 'A';
					$sheet->setCellValue($column.$i, $data['tc_ref_no']);$column++;
					$sheet->setCellValue($column.$i, $data['approved_date']);$column++;
					$sheet->setCellValue($column.$i, $data['customer_number']);$column++;
					$sheet->setCellValue($column.$i, $data['company_name']);$column++;
					$sheet->setCellValue($column.$i, $data['standard_name']);$column++;

					if(count($data['product_data'])>0){
						$itemp = $i;
						foreach($data['product_data'] as $pdtdata){
							$sheet->setCellValue('F'.$itemp, $pdtdata['product_name']);
							$sheet->setCellValue('G'.$itemp, $pdtdata['buyer_name']);
							$sheet->setCellValue('H'.$itemp, $pdtdata['buyer_address']);
							$sheet->setCellValue('I'.$itemp, $pdtdata['country_of_destination']);
							$sheet->setCellValue('K'.$itemp, ''.$pdtdata['invoice_no']);

							$sheet->setCellValue('L'.$itemp, $pdtdata['gross_weight']);
							$sheet->setCellValue('M'.$itemp, $pdtdata['net_weight']);
							$sheet->setCellValue('N'.$itemp, $pdtdata['certified_weight']);

							$sheet->setCellValue('O'.$itemp, $pdtdata['supplier_data']['tc_number']);
							$sheet->setCellValue('P'.$itemp, $pdtdata['supplier_data']['supplier_name']);
							$sheet->setCellValue('Q'.$itemp, $pdtdata['supplier_data']['product_name']);
							
							$itemp++;
						}
					}
					/*
					$sheet->setCellValue('F'.$i, '');
					$sheet->setCellValue('G'.$i, $data['buyer_name']);
					$sheet->setCellValue('H'.$i, $data['buyer_address']);
					$sheet->setCellValue('I'.$i, '');
					*/

					$sheet->setCellValue('J'.$i, $data['seller_name']."\n".$data['seller_address']);
					//$sheet->setCellValue('K'.$i, '');
					//$sheet->setCellValue('L'.$i, $data['gross_weight']);
					//$sheet->setCellValue('M'.$i, $data['net_weight']);
					//$sheet->setCellValue('N'.$i, $data['certified_weight']);
					//$sheet->setCellValue('O'.$i, '');
					//$sheet->setCellValue('P'.$i, '');
					//$sheet->setCellValue('Q'.$i, '');
					$sheet->setCellValue('R'.$i, $data['oss']);
					$sheet->setCellValue('S'.$i, $data['approved_by']);
					
					$i++;				
				}
			}
			
			
			if($post['type']=='submit')
			{
				$responsedata = array('status'=>1,'applications'=>$app_list);
				return $responsedata;
			}
			else
			{	
				$sheet->getStyle('A1:A'.($sheet->getHighestRow()+1))->applyFromArray($styleCenter);	    			
				$sheet->getStyle('C1:C'.($sheet->getHighestRow()+1))->applyFromArray($styleCenter);	
				$sheet->getStyle('K1:O'.($sheet->getHighestRow()+1))->applyFromArray($styleCenter);	
				$sheet->getStyle('B1:B'.($sheet->getHighestRow()+1))->applyFromArray($styleLeft);
				$sheet->getStyle('D1:J'.($sheet->getHighestRow()+1))->applyFromArray($styleLeft);
				$sheet->getStyle('P1:S'.($sheet->getHighestRow()+1))->applyFromArray($styleLeft);

				$sheet->getStyle('A1:S1')->applyFromArray($this->styleWhite);	
				$sheet->getStyle('A1:S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('578CDE'); 
				$sheet->getStyle('A1:S'.($sheet->getHighestRow()+1))->applyFromArray($this->styleVCenter);	
				$sheet->getStyle('A1:S'.($sheet->getHighestRow()+1))->getAlignment()->setWrapText(true); 	  

				$sheet->getStyle('A1:A1')->getAlignment()->setWrapText(true);
				$sheet->setCellValue('A1', 'TC Ref. No.');
				//$sheet->getStyle('A1:S1')->applyFromArray($styleWhite);				
				

				$writer = new Xlsx($spreadsheet);
				$filepath=Yii::$app->params['report_files'].'tc_report_'.date('YmdHis').'.xlsx';
				$writer->save($filepath);							
				
				header('Access-Control-Allow-Origin: *');
				header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
				header('Access-Control-Max-Age: 1000');
				header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
				header('Access-Control-Expose-Headers: Content-Length,Content-Disposition,filename,Content-Type;');
				header('Access-Control-Allow-Headers: Content-Length,Content-Disposition,filename,Content-Type');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($filepath));
				flush(); 
				readfile($filepath);
				die();				
			}
		}
		$responsedata = array('status'=>1,'applications'=>$app_list);
		return $responsedata;
		
		
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
		$Audit = new Audit();
		$arrInvoiceOptionsLabel = $requestModel->arrInvoiceOptionsLabel;
		if(count($arrInvoiceOptionsLabel)>0)
		{
			foreach($arrInvoiceOptionsLabel as $keyInv=>$valInv)
			{
				$arrPaymentStatus[]=array('id'=>$keyInv,'name'=>$valInv);
			}
		}
		
		$apparr = Yii::$app->globalfuns->getAppList();
		$responsedata=array('status'=>1,'appdata'=>$apparr,'paymentStatus'=>$arrPaymentStatus,'audittypedata'=>$Audit->audittypeArr);
		return $this->asJson($responsedata);
	}
	
	
	public function actionGmoReport(){

		if(!Yii::$app->userrole->hasRights(array('tc_report')))
		{
			return false;
		}

		$date_format = Yii::$app->globalfuns->getSettings('date_format');

		$responsedata =array('status'=>0,'message'=>"No Data Found");
		$post = yii::$app->request->post();
		$connection = Yii::$app->getDb();
		$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();


		$userData = Yii::$app->userdata->getData();
		$date_format = Yii::$app->globalfuns->getSettings('date_format');
		$userid=$userData['userid'];
		$user_type=$userData['user_type'];
		$role=$userData['role'];
		$rules=$userData['rules'];

		$report_from_date = strtotime($post['from_date']);
		$report_to_date = strtotime($post['to_date']);

	
		$connection = Yii::$app->getDb();	
		$connection->createCommand("SET SESSION group_concat_max_len = 1000000;")->execute();
		$connection->createCommand("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")->execute();							
		 
		 $command = $connection->createCommand('
		 SELECT
		 raw.tc_number as GOTS_TC_NUMBER,
		 raw.supplier_name as Seller_Of_Certified_Products_Raw_Material,
		 usrr.customer_number as Seller_License_Number_of_certified_organisation,
		 raw.certified_weight as TC_Volume_Total_Certified_weight_RawMaterial,
         rawp.product_name as RawMaterial_Product,
         rawp.lot_number as Additional_Info_RawMaterials,
         buy.name as Buyer_Of_Certified_Products,
         buy.client_number as License_Number_Of_Certified_Organisation, 
         tc.tc_number as TC_Number,
         tc.company_name as Seller_of_certified_products,
         FROM_UNIXTIME(rrc.created_at, "%d/%m/%Y") as Approved_Date,
         tc.total_certified_weight as TC_Total_Certified_Weight,
		 app_product.product_name as Product_details,
		 app_product.product_type_name as Product_details_type,
		 GROUP_CONCAT(DISTINCT  app_product_com.percentage,"%",app_product_com.material_name SEPARATOR ",") as Product_Material_compostion,
		 tcp.product_id as tc_request_product_id,
		 app_product.id as app_product_id
		 FROM `tbl_tc_request` as tc
		 INNER JOIN tbl_tc_request_standard as tcreqstd on tcreqstd.tc_request_id=tc.id
		 INNER JOIN tbl_tc_request_reviewer_comment as rrc on rrc.tc_request_id=tc.id
		 INNER JOIN tbl_tc_request_product as tcp on tcp.tc_request_id=tc.id	
		 INNER JOIN tbl_tc_request_product_input_material as tcpim on tcpim.tc_request_product_id=tcp.id
		 INNER JOIN tbl_tc_raw_material as raw on raw.id=tcpim.tc_raw_material_id
		 INNER JOIN tbl_tc_raw_material_standard as raws on raws.raw_material_id=raw.id
		 left join tbl_users as usrr on usrr.id=raw.created_by
		 inner join tbl_tc_raw_material_product as rawp on rawp.raw_material_id=raw.id
		 inner join tbl_tc_buyer as buy on buy.id=tc.buyer_id
		 inner join tbl_application_unit_product as unit_product on unit_product.id=tcp.product_id
		 inner join tbl_application_product_standard as app_pro_std on  app_pro_std.id=unit_product.application_product_standard_id
		 inner join tbl_application_product as app_product on  app_product.id=app_pro_std.application_product_id
		 inner join tbl_application_product_material as app_product_com on app_product_com.app_product_id = app_product.id 
         where tcreqstd.standard_id in (2)
		 and tc.status=7
	     and rrc.status=1
		 and raws.standard_id in(1)
		 and rrc.created_at >= '.$report_from_date.'
		 and rrc.created_at <= '.$report_to_date.'
		 GROUP BY 
		 tc.tc_number
		 ');
		 
		 
		 
	     $result = $command->queryAll();

		 $app_list=array();
		 if(count($result)>0)
		 {

			if($post['type']!='submit')
			{
				//$arrHeaderLabel=array('TC Number','Seller','Seller License Number','Approved Date','Total Certified Weight','Buyer','Buyer License Number','Raw Material Tc No',
				//'Supplier','Raw Material Certified Weight','Raw Material Products','Raw Material Addition Information');
				
				
				$arrHeaderLabel=array('GOTS TC Number','Supplier Name','Supplier License Number',
				'Standard','Total Certified Weight(Raw Material)','Product Details','Additional Info','Buyer of Certified Products',
				'License Number of Buyer','Tc Number','Seller of Certified Products','Tc Issue Date','Tc Total Certified Weight','Product','Product Type','Material Compostion of Product');
				
				
				$styleWhite = array('font'  => array('name'  => 'Arial','color' => array('rgb' => 'FFFFFF'),'bold'  => true,'size'  => 10,));
				$styleBgColor = array('fill' => array('type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'color' => array('rgb' => '578CDE')));
				$styleCenter = array('alignment' => array('horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,));
				$styleLeft = array('alignment' => array('horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT));
				$styleVCenter = array('alignment' => array('vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER));
				$styleHigh = array('font'  => array('color' => array('rgb' => 'FF0000'),'bold'  => true,));
				$styleMedium = array('font'  => array('color' => array('rgb' => 'F79647'),'bold'  => true,));
				$styleLow = array('font'  => array('color' => array('rgb' => '00B050'),'bold'  => true,));
				
				$spreadsheet = new Spreadsheet();
				$sheet = $spreadsheet->getActiveSheet();
				
				$column = 'A';
				foreach($arrHeaderLabel as $headerKey=>$headerLabel)
				{
					$sheet->setCellValue($column.'1', $headerLabel);
					$defaultWidth=25;
					if($column=='C' || $column=='L' || $column=='M' || $column=='N' ){
						$defaultWidth=10;
					}elseif($column=='B' || $column=='O'){
						$defaultWidth=20;
					}elseif($column=='D'){
						$defaultWidth=35;
					}
					$sheet->getColumnDimension($column)->setWidth($defaultWidth);
					$column++;
				}			
				
				

				$i=2;
			}



			foreach($result as $val)
			{				
				$data=array();
				$data['tc_number']=$val['TC_Number'];
				$data['seller']=$val['Seller_Of_Certified_Products_Raw_Material'];
				$data['seller_lic_no']=$val['Seller_License_Number_of_certified_organisation'];
				$data['standard']="GOTS";
				$data['approved_date']=$val['Approved_Date'];
				$data['total_certified_weight']=$val['TC_Total_Certified_Weight'];
				$data['buyer']=$val['Buyer_Of_Certified_Products'];
				$data['buyer_license_number']=$val['License_Number_Of_Certified_Organisation'];
				$data['raw_material_tc_number']=$val['GOTS_TC_NUMBER'];
				$data['supplier']=$val['Seller_Of_Certified_Products_Raw_Material'];
				$data['raw_material_standard']="";
				$data['raw_material_certified_weight']=$val['TC_Volume_Total_Certified_weight_RawMaterial'];
				$data['products']=$val['RawMaterial_Product'];
				$data['raw_material_additional_info']=$val['Additional_Info_RawMaterials'];
				
				$data['Seller_Of_Certified_Products_Raw_Material']=$val['Seller_Of_Certified_Products_Raw_Material'];
				$data['Seller_License_Number_of_certified_organisation']=$val['Seller_License_Number_of_certified_organisation'];
				$data['RawMaterial_Product']=$val['RawMaterial_Product'];
				$data['Additional_Info_RawMaterials']=$val['Additional_Info_RawMaterials'];
				$data['Buyer_Of_Certified_Products']=$val['Buyer_Of_Certified_Products'];
				$data['License_Number_Of_Certified_Organisation']=$val['License_Number_Of_Certified_Organisation'];
				
				$data['TC_Number']=$val['TC_Number'];
				$data['Seller_of_certified_products']=$val['Seller_of_certified_products'];
				$data['Approved_Date']=$val['Approved_Date'];
				$data['TC_Total_Certified_Weight']=$val['TC_Total_Certified_Weight'];
				$data['Product_details']=$val['Product_details'];
					
				$data['Product_details_type']=$val['Product_details_type'];
				$data['Product_Material_compostion']=$val['Product_Material_compostion'];
				


				if($post['type']=='submit')
				{
					$app_list[]=$data;
				}else{	
				
				   
					//$arrHeaderLabel=array('TC Number','Seller','Seller License Number',
				    //'Buyer','Buyer License Number','Approved Date','Total Certified Weight','Supplier',
				    //'Raw Material Tc No','Raw Material Products','Raw Material Certified Weight','Raw Material Addition Information');

					$column = 'A';
					$sheet->setCellValue('A'.$i, $data['raw_material_tc_number']);$column++;
					$sheet->setCellValue('B'.$i, $data['Seller_Of_Certified_Products_Raw_Material']);$column++;
					$sheet->setCellValue('C'.$i, $data['Seller_License_Number_of_certified_organisation']);$column++;
					$sheet->setCellValue('D'.$i, "GOTS");$column++;
					$sheet->setCellValue('E'.$i, $data['raw_material_certified_weight']);$column++;
					$sheet->setCellValue('F'.$i, $data['RawMaterial_Product']);$column++;
					$sheet->setCellValue('G'.$i, $data['Additional_Info_RawMaterials']);$column++;
					$sheet->setCellValue('H'.$i, $data['Buyer_Of_Certified_Products']);$column++;
					$sheet->setCellValue('I'.$i, $data['License_Number_Of_Certified_Organisation']);$column++;
					$sheet->setCellValue('J'.$i, $data['TC_Number']);$column++;
					$sheet->setCellValue('K'.$i, $data['Seller_of_certified_products']);$column++;
					$sheet->setCellValue('L'.$i, $data['Approved_Date']);$column++;
					$sheet->setCellValue('M'.$i, $data['TC_Total_Certified_Weight']);$column++;
					$sheet->setCellValue('N'.$i, $data['Product_details']);$column++;
					$sheet->setCellValue('O'.$i, $data['Product_details_type']);$column++;
					$sheet->setCellValue('P'.$i, $data['Product_Material_compostion']);$column++;
						
					$i++;

				}
			}
			if($post['type']=='submit')
			{
				$responsedata = array('status'=>1,'gmoreports'=>$app_list);
				return $responsedata;
			}
			else
			{	
				$sheet->getStyle('A1:A'.($sheet->getHighestRow()+1))->applyFromArray($styleCenter);	    			
				$sheet->getStyle('C1:C'.($sheet->getHighestRow()+1))->applyFromArray($styleCenter);	
				$sheet->getStyle('K1:O'.($sheet->getHighestRow()+1))->applyFromArray($styleCenter);	
				$sheet->getStyle('B1:B'.($sheet->getHighestRow()+1))->applyFromArray($styleLeft);
				$sheet->getStyle('D1:J'.($sheet->getHighestRow()+1))->applyFromArray($styleLeft);
				$sheet->getStyle('P1:S'.($sheet->getHighestRow()+1))->applyFromArray($styleLeft);

				$sheet->getStyle('A1:S1')->applyFromArray($this->styleWhite);	
				$sheet->getStyle('A1:S1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('578CDE'); 
				$sheet->getStyle('A1:S'.($sheet->getHighestRow()+1))->applyFromArray($this->styleVCenter);	
				$sheet->getStyle('A1:S'.($sheet->getHighestRow()+1))->getAlignment()->setWrapText(true); 	  

				$sheet->getStyle('A1:A1')->getAlignment()->setWrapText(true);
				$sheet->setCellValue('A1', 'TC Number');
				//$sheet->getStyle('A1:S1')->applyFromArray($styleWhite);				
				

				$writer = new Xlsx($spreadsheet);
				$filepath=Yii::$app->params['report_files'].'gmo_report_'.date('YmdHis').'.xlsx';
				$writer->save($filepath);							
				
				header('Access-Control-Allow-Origin: *');
				header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
				header('Access-Control-Max-Age: 1000');
				header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
				header('Access-Control-Expose-Headers: Content-Length,Content-Disposition,filename,Content-Type;');
				header('Access-Control-Allow-Headers: Content-Length,Content-Disposition,filename,Content-Type');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($filepath));
				flush(); 
				readfile($filepath);
				die();				
			}
		
		 }		 
		 $responsedata = array('status'=>1,'gmoreports'=>$app_list);
		 return $responsedata;
	}

    
}
