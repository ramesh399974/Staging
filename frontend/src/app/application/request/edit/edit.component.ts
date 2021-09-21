import { Component, OnInit, ViewChild, EventEmitter } from '@angular/core';
import { FormGroup, FormBuilder, Validators, FormControl,FormArray,NgForm,NgControl, Form } from '@angular/forms';
import { CountryService } from '@app/services/country.service';
import { StandardService } from '@app/services/standard.service';

import { EnquiryDetailService } from '@app/services/enquiry-detail.service';
import { ProductService } from '@app/services/master/product/product.service';
import { ProcessService } from '@app/services/master/process/process.service';
import { BusinessSectorService } from '@app/services/master/business-sector/business-sector.service';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';

import { Country } from '@app/services/country';
import { State } from '@app/services/state';
import { Standard } from '@app/services/standard';
import {saveAs} from 'file-saver';

import { Product } from '@app/models/master/product';
import { Process } from '@app/models/master/process';
import { Units } from '@app/models/master/units';
import { BusinessSector } from '@app/models/master/business-sector';
import { BusinessSectorGroup } from '@app/models/master/business-sector-group';
import { ProductType } from '@app/models/master/producttype';
import { MaterialComposition } from '@app/models/master/materialcomposition';
import { MaterialType } from '@app/models/master/materialtype';

import { tap,map, startWith,first,switchMap } from 'rxjs/operators'; 
//import { FileUploader, FileLikeObject } from 'ng2-file-upload';

import { ActivatedRoute ,Params, Router } from '@angular/router';
import {LabelGrade} from '@app/models/master/labelgrade';
import {NgbModal, ModalDismissReasons} from '@ng-bootstrap/ng-bootstrap';
import {Observable} from 'rxjs';

import { StandardAdditionService } from '@app/services/change-scope/standard-addition.service';
import { ApplicationDetailService } from '@app/services/application/list/application-detail.service';
import { Application } from '@app/models/application/application';
import { ReductionStandardService } from '@app/services/master/reductionstandard/reductionstandard.service';
import { CbService } from '@app/services/master/cb/cb.service';
import { BrandService } from '@app/services/master/brand/brand.service';

function readBase64(file): Promise<any> {
  var reader  = new FileReader();
  var future = new Promise((resolve, reject) => {
    reader.addEventListener("load", function () {
      resolve(reader.result);
    }, false);

    reader.addEventListener("error", function (event) {
      reject(event);
    }, false);

    reader.readAsDataURL(file);
  });
  return future;
}

@Component({
  selector: 'app-edit',
  templateUrl: '../add/add.component.html',
  styleUrls: ['./edit.component.scss']
})
export class EditComponent implements OnInit {

  unitProductForm:any;  
  @ViewChild('unitProductForm', {static: false}) ngForm: NgForm;
  brandlist: any;
  app_type: any;
  loadingFile: boolean;

  constructor(public brandService: BrandService,private reductionstandard:ReductionStandardService,private modalService: NgbModal,private router:Router,private BusinessSectorService: BusinessSectorService,private processService:ProcessService,private activatedRoute:ActivatedRoute, 
    private fb:FormBuilder,private productService:ProductService,
    private countryservice: CountryService,private standards: StandardService,
     public enquiry:EnquiryDetailService,public errorSummary:ErrorSummaryService,public standardAdditionService:StandardAdditionService,public applicationDetailService:ApplicationDetailService, private CbService:CbService) 
  { }

  unitproductlist=[];
  
  id:number;
  app_id:number;
  standard_addition_id:number;
  applicationData:any;
  addition_type:any;

  sel_brand_ch:number;
  formData:FormData = new FormData();
  standardsChkDb=[];
  unitstandardsChkDb=[];
  assettttt:Array<any>=[];
  countryList:Country[];
  stateList:State[];
  unitStateList:State[];
  standardList:Standard[];
  productList:Product[];
  processList: Process[];
  bsectorList:BusinessSector[];
  bsectorgroupList:BusinessSectorGroup[];

  productEntries:any=[];
  standardEntries:any[]=[];
  processEntries:Process[] =[];
  unitEntries:Units[] = []; 
  unitstandardList:Array<any>=[];
  company_unit_typeErrors='';

  enquiryForm : FormGroup;
  selectedOrderIds:any = {}; 
  standardsLength:number=0;
  submitted:number = 0;
  submittedSuccess:number = 0;
  submittedError:number = 0;
  productErrors='';
  wastageErrors='';
  processErrors = '';
  
  standardErrors = '';
  unitErrors = '';
  showCert = false;
  brand_file='';
  
  
  error:any;
  salutationList = [{"id":1,"name":"Mr"},{"id":2,"name":"Mrs"},{"id":3,"name":"Ms"},{"id":4,"name":"Dr"}];

  consultant_name:string;
  consultant_company:string;

  unitstandard_error = '';
  addstandard_error ='';
  unitIndex:number=null;
  company_file = '';
  productTypeList:ProductType[];


  producttypeErrors='';
  compositionErrors = '';
  grade_error = '';
  maxDate = new Date();
  
/*
  public uploader:FileUploader = new FileUploader({
    url: URL, 
    disableMultipart:true
    });
 
  fileObject: any;
 */
  
  options: string[];
  filteredOptions: Observable<string[]>;
  loading:any=[];
  buttonDisable = false;
  
  standardAdditionList:any=[];
  applicationStandardList:Application[];
  unitStandardsDisable:any={};
  unitBSectorDisable:any={};
  ceritifedByOtherCertificationBodyFormStatus = true;
  
  reductionStandardList:any = [];
  cbList:any=[];
  appdataloading:any=false;
  ngOnInit() { 

   


    this.id = this.activatedRoute.snapshot.queryParams.id;

    this.app_id = this.activatedRoute.snapshot.queryParams.app_id;
    this.app_type = this.activatedRoute.snapshot.queryParams.type;
    this.standard_addition_id = this.activatedRoute.snapshot.queryParams.standard_addition_id;	
    this.appdataloading = true;
    this.reductionstandard.getStandardList().pipe(first()).subscribe(ress => {
      this.reductionStandardList = ress['standards'];
    });

    this.brandService.getData().subscribe(res=>{
      this.brandlist = res.data;
    });

    this.getBSectorStandardWise();
    this.enquiry.getEnquiryDetails(this.id).
    pipe(
      tap(res=>{
        if(res && res.country_id){
          this.countryservice.getStates(res.country_id).pipe(first()).subscribe(res => {
              this.stateList = res['data'];
          });
        }
        
        if(res.audit_type!=1 && res.audit_type!=2 && res.audit_type!=4)
        {
          this.ceritifedByOtherCertificationBodyFormStatus = false;
        }
    		if(this.standard_addition_id!==null && this.standard_addition_id>0)
    		{     
          //this.ceritifedByOtherCertificationBodyFormStatus = false;
    			this.standardAdditionService.getStandardAdditionList({app_id:this.app_id,standard_addition_id:this.standard_addition_id}).pipe(first()).subscribe(resadd => {
    			   this.standardAdditionList = resadd['standardaddition'];
             this.standards.getStandard().pipe(first()).subscribe(ress => {
             
              this.standardList = ress['standards'].filter(stdx=>this.standardAdditionList.includes(stdx.id));
              
              res.standard_ids.forEach(val=>{
                this.standardsChkDb.push(""+val+"");
                this.selStandardIds.push(""+val+"");
              }); 
              this.selStandardList = this.standardList.filter(x=>this.selStandardIds.includes(x.id));   
              this.setCertificationBody(res['applicationcertifiedbyothercb']);
            });
          });

          
          
    		}else{
          this.standards.getStandard().pipe(first()).subscribe(ress => {
              this.standardList = ress['standards']; 
          
            
              res.standard_ids.forEach(val=>{
                this.standardsChkDb.push(""+val+"");
                this.selStandardIds.push(""+val+"");
              });
              this.selStandardList = this.standardList.filter(x=>this.selStandardIds.includes(x.id));
              this.setCertificationBody(res['applicationcertifiedbyothercb']);
             
          });
        }
        
        


        if(this.app_id!==null && this.app_id>0)
        {
          this.applicationDetailService.getApplicationStandard(this.app_id).pipe(first()).subscribe(res => {
            this.applicationStandardList = res['standardaddition'];
          });			
        }
		
        //this.standardsChkDb = res.standard_ids;
        
      })
      ,first()
    )
    .subscribe(res => {
      
      this.applicationData = res;
      
      this.productListDetails = res.productDetails;
      this.productEntries = res.products;
       if(res && res.units)
      res.units.forEach((val)=>{
        //this.standardEntries = [];
        this.processEntries= val.process_ids;

        if(val.certified_standard && val.certified_standard.length>0){
          val.certified_standard.forEach((standard)=>{
            let expobject:any=[];
            
            let fname = [];
            standard.files.forEach(element => { 
              fname.push({name: element.name,added:0,deleted:0,type:element.type,fileadded:0});
            });

            expobject["id"] = standard.id;
            expobject["name"] = standard.standard;//this.registrationForm.get('expname').value;
            expobject["uploadedFiles"] = fname;
            expobject["uploadedFileNames"] = fname;
            expobject["license_number"] = standard.license_number;
            expobject["expiry_date"] = standard.expiry_date;
            
            
            this.standardEntries.push(expobject);
          })
        }

        if(val.standards && val.standards.length>0)
        {
          let standardvals=val.standards;
          this.BusinessSectorService.getBusinessSectorsbystds({standardvals}).subscribe(res => {
            this.bsectorList = res['bsectors'];
          });	
        }

        let unitProductList:any=[];
        if(val.product_details && val.product_details.length>0){
          unitProductList = [...val.product_details]
        }
        
        //val.products.forEach((productd)=>{
          //unitProductList.push({...this.productListDetails.find(x=>x.autoid == productd.pdt_id)});
        //});

       // this.unitStandardsDisable:any ={};
       // this.unitBSectorDisable:any = {};
        if(val.existsstandards && val.existsstandards.length >0){
          this.unitStandardsDisable[""+val.id] = [...val.existsstandards].map(String);
        }
        if(val.existsbsector_ids && val.existsbsector_ids.length >0){
          this.unitBSectorDisable[""+val.id] = [...val.existsbsector_ids].map(String);
        }
       

        let expobject:Units;
        expobject = {
          "unit_type":val.unit_type,
          "unit_name": val.name,
          "unit_id": val.id,

          "unit_exists" : [...val.existsstandards].map(String),
          "business_sector_exists" : [...val.existsbsector_ids].map(String),
          "sel_processexists" :[...val.existsprocess_ids],

          "addition_type": val.addition_type,
          "unit_address":val.address,
          "unit_zipcode":val.zipcode,
          "unit_country_id":val.country_id,
          "unit_state_id":val.state_id,
          "business_sector_id":val.bsector_ids,
          "bsectorList":val.bsector_data,
          "unitProductList":unitProductList,

          "unit_country_name":val.country_id_name,
          "unit_state_name":val.state_id_name,
          "unit_city":val.city,
          "no_of_employees":val.no_of_employees,
          
          "sel_standard":this.standardEntries,
          "sel_process":this.processEntries,
         // "processList":val.process_data,
          "unitStateList": val.state_list,
          "selUnitStandardList":val.standards,
          deleted:0
          //"certFile" : this.uploadedFiles
        }
        
        this.unitEntries.push(expobject);
       
        this.standardEntries = [];
        this.processEntries = [];

      })
      
      this.company_file =  res.company_file;


      this.enquiryForm.patchValue({
        company_name:res.company_name,
        company_address:res.address,
        zipcode:res.zipcode,
        city:res.city,
        tax_no:res.tax_no,
        state_id:res.state_id,
        country_id:res.country_id,
        company_file: res.company_file,
        salutation:res.salutation,

        sel_cons_ch:(res.sel_cons_ch)?res.sel_cons_ch:"2",
        sel_cons : (res.sel_cons)?res.sel_cons:'',

    
        title:res.title,
        first_name:res.first_name,
        sel_brand_ch:(res.sel_brand_ch)?res.sel_brand_ch:"2",
        brand_id:(res.sel_brand_ch==2 || res.sel_brand_ch==null)?'':res.brandids,
        last_name:res.last_name,
        job_title:res.job_title,
        company_telephone:res.telephone,
        company_email:res.email_address,
        preferred_partner_id:res.preferred_partner_id,
        
      });

      this.brand_file=res.brand_file;
      this.appdataloading = false;
    },
    error => {
        this.error = {summary:error};
        this.appdataloading = false;
    });

    
      
      /*
    this.standardsChkDb.forEach((val)=>{
          //standardsFormArray.push(new FormControl(val));
          
          this.onChange(val,true);
        });
      */

    this.countryservice.getCountry().pipe(first()).subscribe(res => {
      
      this.countryList = res['countries'];
    });
 

	  this.standards.getStandard().pipe(first()).subscribe(res => {
      this.standardList = res['standards'];   
      
      
    });
    
    this.enquiry.getApplicationchecklist({app_id:this.id}).subscribe(res => {
      this.appchecklist = res['appchecklist'];
      this.answerArr = res['answerArr'];

      this.appchecklist.forEach((x,index)=>{
        
        this.t.push(this.fb.group({
            answer: [x.answer, [Validators.required]],
            comment: [x.comment, [Validators.required]],
            checklistfile: ['']
        }));
        this.uchecklistfiles[x.id] = x.document;
      })
    });

    this.productService.getProductList().pipe(first()).subscribe(res => {
      this.productList = res['products'];
      this.materialTypeList = res['material_type']; 
    });
    
    this.processService.getProcessList().pipe(first()).subscribe(res => {
      this.processList = res['processes'];      
    });

    
    
  
    //getProductList

      this.enquiryForm = this.fb.group({
      appchecklistfield:new FormArray([]),
	    certifiedbyothercertificationbody:new FormArray([]),
      company_name:['',[Validators.required]],
      company_file:[''],
      company_address:['',[Validators.required]],
      zipcode:['',[Validators.required]],
      city:['',[Validators.required]],
      state_id:['',[Validators.required]],
      country_id:['',[Validators.required]],
      tax_no:['',[this.errorSummary.noWhitespaceValidator,Validators.maxLength(35)]],	
      salutation:['',[Validators.required]],
      brand_id : ['',[Validators.required]],
      title:[''],
      first_name:['',[Validators.required]],
      last_name:['',[Validators.required]],
      job_title:['',[Validators.required]],
      company_telephone:['',[Validators.required]],
      company_email:['',[Validators.required, Validators.email]],
      sel_brand_ch:['',[Validators.required]],
      sel_brand : ['',[Validators.required]],
      sel_cons_ch:['',[Validators.required]],
      consultant_name:[''],
      consultant_company:[''],

      standardsChk:  this.fb.array([]),
      unitstandardsChk:  this.fb.array([]),
      productsWastage:  this.fb.array([]),
      unitsLists:  this.fb.array([]),

      unit_name:['',[Validators.required]],
      unit_id:[''],
      
      unit_address:['',[Validators.required]],
      unit_zipcode:['',[Validators.required]],
      unit_city:['',[Validators.required]],
      unit_state_id:['',[Validators.required]],
      business_sector_id:['',[Validators.required]],
      //unit_product_id:['',[Validators.required]],
      no_of_employees:['',[Validators.required,Validators.min(1),Validators.pattern('^[0-9]*$')]],
      unit_country_id:['',[Validators.required]],
      certFile:[''],
      auditReportFile:[''],
      

      sel_process:['',[Validators.required]],
      sel_reduction:['',[Validators.required]],
      sel_standard:[''],
      license_number:[''],
      expiry_date:[''],
      product: ['',[Validators.required]],
      wastage: ['',[Validators.required,Validators.pattern('^[0-9]+(\.[0-9]{1,2})?$'),Validators.max(100)]],
      product_type:['',[Validators.required]],

      material:['',[Validators.required]],
      material_type:['',[Validators.required]],
      material_percentage:['',[Validators.required,Validators.pattern('^[0-9]+(\.[0-9]{1,2})?$'),Validators.max(100)]],
      composition_standard:['',[Validators.required]],
      label_grade:['',[Validators.required]],
      preferred_partner_id:['']
      //this.fb.array([]),
      /*
      product:['',[Validators.required]],
      wastage:['',[Validators.required]],
      */
      /*
      product_certified:  this.fb.array([]),
      unit_details:  this.fb.array([]),
    */
    });
    
     //let appchecklist = [{id:1,name:'test 1'},{id:2,name:'test 2'}];
    
    
    this.CbService.getCbList().subscribe(res => {
      this.cbList = res['cbs'];
    });

    this.enquiryForm.get('sel_cons_ch').valueChanges.subscribe(val => {
      //console.log('val',val);
      const consultant_name = this.enquiryForm.get('consultant_name');
      const consultant_company = this.enquiryForm.get('consultant_company');
      if (val === "1") {
        consultant_name.setValidators(Validators.required);
        consultant_company.setValidators(Validators.required);
        this.enquiryForm.updateValueAndValidity();
      }else if (val === "2") {
        consultant_name.clearValidators();
        consultant_company.clearValidators();
        this.enquiryForm.updateValueAndValidity();
      }
     
    });

  }

  setCertificationBody(applicationcertifiedbyothercb:any){
    if(this.ceritifedByOtherCertificationBodyFormStatus)
    {
      //this.standardsChkDb.map(String);
      this.standardList.forEach(val=>{
      
      let validity_date:any='';
      let certification_body='';
      let certificationfile='';
      if(applicationcertifiedbyothercb.length>0)
      {
        if(this.selStandardIds.includes(val.id))      
        {       
          let othercertBody = applicationcertifiedbyothercb.find(x=> x.standard_id==val.id);
          if(othercertBody){        
            validity_date=this.errorSummary.editDateFormat(othercertBody.validity_date);
            certification_body=othercertBody.certification_body;          
            this.certifiedbyothercbfiles[val.id] = othercertBody.certification_file;
          }             
        }
      }
      
      this.ocb.push(this.fb.group({
        validity_date: [validity_date],
        certification_body: [certification_body],
        certificationfile: [certificationfile]
      }));
      //, Validators.required
      });
    }
  }
/*
  private _filter(searchname: string): Observable<any[]> {
    const filterValue = searchname.toLowerCase();
    return this.productService.getCompositionList(searchname);
  }
*/
  getSelectedValue(type,val)
  {
    if(type=='business_sector_id')
    {
    let name = '';
      let bsector = this.bsectorList.find(x=> x.id==val);
      if(bsector){
        name = bsector.name;
      }
      return name;
    }
    else if(type=='brand_id'){
      let name='';
      let brandL = this.brandlist.find(x=>x.id==val);
      if(brandL){
        name = brandL.brand_name;
      }
      return name;
    }
  }

  modalss:any;
  guidanceContent='';
  openguidance(content,type) {

    if(type=='scopeholder')
    {
      this.guidanceContent='Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.';
    }
    else if(type=='standards')
    {
      this.guidanceContent='Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.';
    }
    else if(type=='product')
    {
      this.guidanceContent='Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.';
    }
    else
    {
      this.guidanceContent='Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.';
    }
    
    this.modalss = this.modalService.open(content, {ariaLabelledBy: 'modal-basic-title',centered: true});
    this.modalss.result.then((result) => {

    }, (reason) => {
      
    });
  }
  
  open(content,arg='') {
    this.modalss = this.modalService.open(content, {ariaLabelledBy: 'modal-basic-title',centered: true});
    this.modalss.result.then((result) => {

    }, (reason) => {
      
    });
  }

  getBsectorList(reqType="unit",unit_id:any=''){
    let standardvals=[];
    if(reqType =="main"){
      this.selStandardList.forEach(val=>{
        standardvals.push(val.id);
      })
    }else{
      standardvals=  [...this.selUnitStandardList];//this.standardsChkDb.concat(this.enquiryForm.get('standardsChk').value);
    }
    
    
    //let standardvals= [...this.selUnitStandardList];//this.standardsChkDb.concat(this.enquiryForm.get('standardsChk').value);
    if(standardvals.length>0)
    {
      this.BusinessSectorService.getBusinessSectorsbystds({standardvals}).subscribe(res => {
        this.bsectorList = res['bsectors'];
        this.enquiryForm.patchValue({business_sector_id:''});
        //this.unitBSectorDisable[""+val.id]
        if(unit_id=='' || unit_id==0){
          unit_id = this.f.unit_id.value;
        }
        
        if(unit_id){
          /*if(this.unitBSectorDisable[""+unit_id] && this.unitBSectorDisable[""+unit_id].length>0){
            this.enquiryForm.patchValue({business_sector_id:this.unitBSectorDisable[""+unit_id]});
          }*/
        }
        
        if(this.unitEntries[0] !== undefined && reqType =="main"){
          this.unitEntries[0]['bsectorList'] = [...this.bsectorList];
        }
      });	
    }else{		
      this.bsectorList = [];
      this.enquiryForm.patchValue({business_sector_id:''});		
      if(this.unitEntries[0] !== undefined && reqType =="main"){
        this.unitEntries[0]['bsectorList'] = [];
      }
    }
    
  }

  getBsectorgroupList(){

    this.processEntries = [];
    this.processList = [];
    

    let standardvals=[];
    if((this.unitIndex ===null && this.unitEntries.length ==0 ) || this.unitIndex==0){
      this.selStandardList.forEach(val=>{
        standardvals.push(val.id);
      })
      //standardvals=  [...this.selStandardList];
    }else{
      standardvals=  [...this.selUnitStandardList];
    }
    //let standardvals= [...this.selUnitStandardList];//this.standardsChkDb.concat(this.enquiryForm.get('standardsChk').value);
    let bsectorvals=this.enquiryForm.controls.business_sector_id.value;
    if(standardvals.length>0  && bsectorvals.length>0)
    {
      this.BusinessSectorService.getBusinessSectorGroupsbystds({standardvals,bsectorvals}).subscribe(res => {
        this.processList = res['processes'];
        this.enquiryForm.patchValue({sel_process:''});
      });	
    }else{		
      this.processList = [];
      this.enquiryForm.patchValue({sel_process:''});		
    }


  }

  getProductType(productid){
    this.productTypeList = [];
	this.materialList = [];
	this.productMaterialList = [];
	this.enquiryForm.patchValue({product_type:'',material:'',material_type:''});
	
	if(productid>0)
	{
		this.loading['producttype'] = 1;
		this.productService.getProductTypes(productid).pipe(first()).subscribe(res => {
		  this.productTypeList = res['data'];
		  this.materialList = [];
		  this.productMaterialList = [];
		  this.loading['producttype'] = 0;
		});
	}	
  }
  getProductTypeOnEdit(productid,product_typeid){
    this.loading['producttype'] = 1;
    this.productService.getProductTypes(productid).pipe(first()).subscribe(res => {
      this.productTypeList = res['data']; 
      this.getProductMaterial(product_typeid,0);
      this.loading['producttype'] = 0;
      //this.materialList = [];
      //this.productMaterialList = [];
    });
  }

  getStandardGrade(standardid){
	this.labelGradeList = [];	
	this.enquiryForm.patchValue({label_grade:''});
	
	if(standardid>0)
	{
		this.loading['labelgrade'] = 1;
		this.productService.getStandardLabel(standardid).pipe(first()).subscribe(res => {
		  this.labelGradeList = res['data'];   
		  this.loading['labelgrade'] = 0;   
		});
	}
  }
  
  getProductMaterial(product_typeid,makeempty=1){
	//this.productMaterialList = [];
	this.enquiryForm.patchValue({material:'',material_type:''});
	
    if(product_typeid>0)
    {
		this.loading['material'] = 1;
		this.productService.getMaterial(product_typeid).pipe(first()).subscribe(res => {
		  this.materialList = res;
		  this.loading['material'] = 0;
		  if(makeempty){
			this.productMaterialList = [];
		  }
		  
		  //this.productMaterialList = [];
		});
	}
  }


  /*
  Product Material Section
  */
  productMaterialList:Array<any> = [];
  productmaterial_error = '';
  materialTypeList:MaterialType[]=[];
  materialList:MaterialComposition[]=[];
  
  removeProductMaterial(Id:number) {
    let index= this.productMaterialList.findIndex(s => s.material_id ==  Id);
    if(index !== -1)
      this.productMaterialList.splice(index,1);
  }
  touchProductMaterial(){
    this.f.material.markAsTouched();
    this.f.material_type.markAsTouched();
    this.f.material_percentage.markAsTouched();
  }
  addProductMaterial(){
    this.f.material.setValidators([Validators.required]);
    this.f.material_type.setValidators([Validators.required]);
    this.f.material_percentage.setValidators([Validators.required,Validators.pattern('^[0-9]+(\.[0-9]{1,2})?$'),Validators.max(100)]);

    this.f.material.updateValueAndValidity();
    this.f.material_type.updateValueAndValidity();
    this.f.material_percentage.updateValueAndValidity();


    this.touchProductMaterial();
    let material = this.enquiryForm.get('material').value;
    let material_type = this.enquiryForm.get('material_type').value;
    let material_percentage = this.enquiryForm.get('material_percentage').value;

    let selmaterial = this.materialList.find(s => s.id ==  material);
    let selmaterialtype = this.materialTypeList.find(s => s.id ==  material_type);
    this.productmaterial_error = '';
    
    if(material=='' || material_type=='' || material_percentage==''  || this.f.material_percentage.errors){
      return false;
    }
    
    let entry= this.productMaterialList.findIndex(s => s.material_id ==  material);
    let expobject = {material_id: selmaterial.id,
                material_name: selmaterial.name,
                material_percentage:material_percentage,
                material_type_id:selmaterialtype.id,
                material_type_name:selmaterialtype.name}

    if(entry === -1){
      this.productMaterialList.push(expobject);
    }else{
      this.productMaterialList[entry] = expobject;
    }
    
    this.enquiryForm.patchValue({
      material: '',
      material_type:'',
      material_percentage:''
    });
    this.f.material.setValidators([]);
    this.f.material_type.setValidators([]);
    this.f.material_percentage.setValidators([]);

    this.f.material.updateValueAndValidity();
    this.f.material_type.updateValueAndValidity();
    this.f.material_percentage.updateValueAndValidity();

    //this.materialList = [];
    this.productmaterial_error = '';
  }
  editProductMaterial(Id:number){
    let mat= this.productMaterialList.find(s => s.material_id ==  Id);

    //this.getProductMaterial(mat.product_type_id);

    this.enquiryForm.patchValue({
      material: mat.material_id,
      material_type: mat.material_type_id,
      material_percentage:mat.material_percentage
    });
  }


  

  /*
  Product Standard Section
  */
  productStandardList:Array<any> = [];
  productstandard_error = '';
  labelGradeList:LabelGrade[]=[];
  removeProductStandard(standardId:number) {
    let index= this.productStandardList.findIndex(s => s.standard_id ==  standardId);
    if(index !== -1)
      this.productStandardList.splice(index,1);
  }
  touchProductStandard(){
    this.f.composition_standard.markAsTouched();
    this.f.label_grade.markAsTouched();
  }
  addProductStandard(){
    this.productstandardgrade_error = '';
    this.f.composition_standard.setValidators([Validators.required]);
    this.f.label_grade.setValidators([Validators.required]);

    this.f.composition_standard.updateValueAndValidity();
    this.f.label_grade.updateValueAndValidity();

    this.touchProductStandard();
    let standardId = this.enquiryForm.get('composition_standard').value;
    let label_grade = this.enquiryForm.get('label_grade').value;

    let selstandard = this.standardList.find(s => s.id ==  standardId);
    let sellabel = this.labelGradeList.find(s => s.id ==  label_grade);
    this.productstandard_error = '';
    
    if(standardId==''){
      //this.productstandard_error = 'Please select the Standard';
      return false;
    }
    
    let entry= this.productStandardList.findIndex(s => s.standard_id ==  standardId);
    let expobject:any=[];
    expobject["standard_id"] = selstandard.id;
    expobject["standard_name"] = selstandard.name;//this.registrationForm.get('expname').value;
    expobject["label_grade"] = sellabel.id;
    expobject["label_grade_name"] = sellabel.name;
    if(entry === -1){
      this.productStandardList.push(expobject);
    }else{
      if(this.productIndex!==null){
        let prd= this.productEntries[this.productIndex].productStandardList.find(xx=>xx.standard_id == selstandard.id);
        if(prd !== undefined){
          expobject['pdt_index'] = prd.pdt_index;
        }
        
      }
      
      
      //expobject[pdt_index] = this.productStandardList[entry].pdt_index;
      this.productStandardList[entry] = expobject;
      
    }
    
    this.enquiryForm.patchValue({
      composition_standard: '',
      label_grade:''
    });
    this.f.composition_standard.setValidators([]);
    this.f.label_grade.setValidators([]);

    this.f.composition_standard.updateValueAndValidity();
    this.f.label_grade.updateValueAndValidity();

    this.labelGradeList = [];
	
	  this.std_with_product_std_error='';
  }
  editProductStandard(standardId:number){
    let prd= this.productStandardList.find(s => s.standard_id ==  standardId);

    this.getStandardGrade(prd.standard_id);

    this.enquiryForm.patchValue({
      composition_standard: prd.standard_id,
      label_grade:prd.label_grade
    });
  }




  /*
  Product Section
  */
  productIndex = null;
  productMasterList = [];
  productstandardgrade_error = '';
  removeProduct(index:number) {
    //let index= this.productEntries.findIndex(s => s.id ==  productId);
    //if(index != -1)
    let prd= this.productEntries[index];
    
    if(prd){
      let prd_id = prd.id;
      
      this.productEntries.splice(index,1);

      let prdexists = this.productEntries.findIndex(s=>s.id==prd_id);

      
      if(prdexists === -1){
        let masterindex = this.productMasterList.findIndex(s=>s.id==prd_id);
        if(masterindex !==-1){
          this.productMasterList.splice(masterindex,1);
        }
      }


      this.unitIndex = null;
      this.showCert = false;



      prd.productStandardList.forEach(selstandard=>{
        
          let listdetailsindex = this.productListDetails.findIndex(s => s.pdt_index ==  selstandard.pdt_index);
          if(listdetailsindex !== -1){
            this.productListDetails.splice(listdetailsindex,1);


            this.unitEntries.forEach((val,index)=>{
              if(!val.deleted){
                let unitProductList = val.unitProductList;

              
                let unitlistpdtindex = unitProductList.findIndex(xp=> xp.pdt_index == selstandard.pdt_index );
                if(unitlistpdtindex !== -1){
                  unitProductList.splice(unitlistpdtindex,1);
                }
                let expobject = {...val,unitProductList:unitProductList};
                this.unitEntries[index] = expobject;
              }
              
            });


          }
        

      })
    }

    /*
    this.addProductDetails();

    this.unitEntries.forEach((val,index)=>{
      let expobject = {...val,unitProductList:[]};
      this.unitEntries[index] = expobject;
    });
    */
  }
  
  productReset(){
    this.f.product.setValidators([]);
    this.f.wastage.setValidators([]);
    this.f.product_type.setValidators([]);
    this.f.material.setValidators([]);
    this.f.composition_standard.setValidators([]);
    this.f.label_grade.setValidators([]);

    
    //this.enquiryForm.get('wastage').setValidators([]);
    //this.enquiryForm.controls.setErrors(null);
    this.enquiryForm.patchValue({
      product: '',
      wastage:'',
      product_type:'',
      material:'',
      composition_standard: '',
      label_grade:''
      
    });
    this.productStandardList = [];
    this.labelGradeList = [];
    this.productTypeList = [];
    this.productIndex=null;
    this.materialList = [];
    this.productMaterialList = [];
    
    this.productmaterial_error = ''; 
    this.productErrors = '';
    this.wastageErrors = '';
    this.productstandard_error= '';
    this.productstandardgrade_error='';
    //this.enquiryForm.controls['product'].clearValidators()
    
    //this.enquiryForm.controls['product'].setValidators([Validators.required]);

    
  }
  productListDetails:any=[];
  unitvalproductErrors = '';
  addProduct(){
    this.f.product.setValidators([Validators.required]);
    this.f.wastage.setValidators([Validators.required,Validators.pattern('^[0-9]+(\.[0-9]{1,2})?$'),Validators.max(100)]);
    this.f.product_type.setValidators([Validators.required]);
    

    this.f.product.updateValueAndValidity();
    this.f.wastage.updateValueAndValidity();
    this.f.product_type.updateValueAndValidity();
    
    if(this.productStandardList.length<=0){

      this.f.composition_standard.setValidators([Validators.required]);
      this.f.label_grade.setValidators([Validators.required]);

      this.f.composition_standard.updateValueAndValidity();
      this.f.label_grade.updateValueAndValidity();
    }

    this.touchProduct();
    let productId:number = this.enquiryForm.get('product').value;
    let wastage = this.enquiryForm.get('wastage').value;
    let product_type = this.enquiryForm.get('product_type').value;
    
    
    
    let materialcomposition = [];
    let materialcompositionname = '';
    let materialpercentage:any=0;
    if(this.productMaterialList.length > 0){

      this.productMaterialList.forEach((val)=>{
        materialcomposition.push(val.material_percentage+'% '+val.material_name);
        materialpercentage = parseFloat(materialpercentage) + parseFloat(val.material_percentage);
      });

      materialcompositionname = materialcomposition.join(' + ');
     
    }

    this.unitvalproductErrors = '';
    if(this.productEntries.length>0){
      
      this.productEntries.forEach((val,index) => {
        let proddesc = val.product_type_id;
        let prodcat = val.id;
       
        if((this.productIndex!=null && this.productIndex !=index)|| this.productIndex===null ){
          
          //if(prodcat== productId && proddesc == product_type ){
          //  this.unitvalproductErrors = 'Product with same Category & Description was already added';
          //}
        }
      });

    }

    this.productErrors = '';
    this.wastageErrors = '';
    this.productmaterial_error = '';
    this.unitproductErrors = '';
    this.productstandardgrade_error = '';
    let productStandardListLength = this.productStandardList.length;
    let selStandardListLength = this.selStandardList.length;
    
    //if(this.unitvalproductErrors != '' || selStandardListLength!= productStandardListLength || productId <=0 || productId=== null || wastage.toString().trim()=='' || product_type==''  || this.productStandardList.length<=0 || this.f.wastage.errors || this.productMaterialList.length<=0 || materialpercentage!= 100){
	if(this.unitvalproductErrors != '' || productId <=0 || productId=== null || wastage.toString().trim()=='' || product_type==''  || this.productStandardList.length<=0 || this.f.wastage.errors || this.productMaterialList.length<=0 || materialpercentage!= 100){
     /* if(productId<=0){
        this.productErrors = 'Please select the Product';
      }
      if(wastage.trim()==''){
        this.wastageErrors = 'Please enter the Wastage';
      }
      */
      if(materialpercentage != 100){
        this.productmaterial_error = 'Total material percentage should be equal to 100';
      }
      if(this.productStandardList.length<=0){
        this.productstandardgrade_error = 'Please add standard and label grade';
      }
      if(this.productMaterialList.length<=0){
        this.productmaterial_error = 'Please add product material';
      }
	  
	  /*
      if(selStandardListLength!= productStandardListLength){
        this.productstandardgrade_error = 'Please add all the standard with label grade for the product';
      }
	  */

      return false;
    }
    let selproduct = this.productList.find(s => s.id ==  productId);
    let selproducttype = this.productTypeList.find(s => s.id ==  product_type);
    
    
    
    let prdexpobject:any={};

    if(this.productIndex==null){
      let expobject:any=[];
     
      expobject["id"] = selproduct.id;
      expobject["name"] = selproduct.name;
      

      expobject["product_type_id"] = selproducttype.id;
      expobject["product_type_name"] = selproducttype.name;
      expobject["wastage"] = wastage;
      expobject["productMaterialList"] = this.productMaterialList;
      expobject["materialcompositionname"] = materialcompositionname;
      expobject["addition_type"] = 1;
      //this.productListDetails.push(expobject);
      prdexpobject = {...expobject};
      
      //this.productListDetails.push(prdexpobject);
      expobject["productStandardList"] = this.productStandardList;
      this.productEntries.push(expobject);
      //this.addProductDetails();
      this.newaddProductDetails(expobject,null);
    }else{
      //let entry = this.productEntries[this.productIndex];
      let entry = [];
      //if(this.productIndex != -1)
      //  this.productListDetails.splice(this.productIndex,1);
      
      let passentry = {...this.productEntries[this.productIndex]};
      entry["id"] = selproduct.id;
      entry["name"] = selproduct.name;
      entry["product_type_id"] = selproducttype.id;
      entry["product_type_name"] = selproducttype.name;
      entry["wastage"] = wastage;
      entry["productMaterialList"] = this.productMaterialList;
      entry["materialcompositionname"] = materialcompositionname;
      entry["addition_type"] = passentry.addition_type?passentry.addition_type:0;
      
      //this.productListDetails = this.productListDetails.filter(x=>x.pdt_index!=this.productIndex);
      prdexpobject = {...entry};
      
      
      entry["productStandardList"] = this.productStandardList;

      
      this.productEntries[this.productIndex] = entry;
      //this.addProductDetails();
      this.newaddProductDetails(entry,this.productIndex,passentry);
       
      //this.productEntries[this.productIndex] = entry;
    }
    
    let prdid = this.productMasterList.findIndex(s=>s.id==selproduct.id);
    if(prdid===-1){
      this.productMasterList.push({id:selproduct.id,name:selproduct.name});
    }
    this.showProductFn();

    
    this.unitEntries.forEach((val,index)=>{
      //let expobject = {...val,unitProductList:[]};
      //this.unitEntries[index] = expobject;
    });
    
    //this.productReset();
  }
  touchProduct(){
    this.f.product.markAsTouched();
    this.f.wastage.markAsTouched();
    
    this.f.product_type.markAsTouched();
  }

  newaddProductDetails(expobject,productIndex:any=null,passentry:any={}){
     
    if(productIndex==null){
      
      productIndex = this.productEntries.length -1;
      
      let pdt_index:any = 0;//this.productListDetails.length; 

      if(this.productListDetails && this.productListDetails.length>0){
        pdt_index = this.productListDetails.reduce((highestnum, curdata) => curdata.pdt_index>highestnum?curdata.pdt_index:highestnum, 0) + 1;
      }
      
       
      let selproduct:any = expobject;
       
        let entry = [];
        entry["id"] = selproduct.id;
        entry["name"] = selproduct.name;
        entry["product_type_id"] = selproduct.product_type_id;
        entry["product_type_name"] = selproduct.product_type_name;
        entry["wastage"] = selproduct.wastage;
        entry["productMaterialList"] = selproduct.productMaterialList;
        entry["materialcompositionname"] = selproduct.materialcompositionname;
        let prdexpobject = {...entry};

        
        selproduct.productStandardList.forEach(selstandard=>{
          
          prdexpobject["standard_id"] = selstandard.standard_id;
          prdexpobject["standard_name"] = selstandard.standard_name;//this.registrationForm.get('expname').value;
          prdexpobject["label_grade"] = selstandard.label_grade;
          prdexpobject["label_grade_name"] = selstandard.label_grade_name;
          prdexpobject["pdt_index"] = pdt_index;
          prdexpobject["productIndex"] = productIndex;
          
          let stdindex = this.productEntries[productIndex].productStandardList.findIndex(xx=>xx.standard_id == selstandard.standard_id);
          
          if(stdindex !== -1){
            this.productEntries[productIndex].productStandardList[stdindex].pdt_index = pdt_index;
          }
          
           
          this.productListDetails.push({...prdexpobject});
          pdt_index++;
        })

        
    }else{
     
      //let pdt_index = this.productListDetails.length; 
      let pdt_index:any = 0;

      if(this.productListDetails && this.productListDetails.length>0){
        pdt_index = this.productListDetails.reduce((highestnum, curdata) => curdata.pdt_index>highestnum?curdata.pdt_index:highestnum, 0) + 1;
      }

      let curproductEntries:any = passentry;
      let selproduct:any = expobject;

      let entry = [];
      entry["id"] = selproduct.id;
      entry["addition_type"] = selproduct.addition_type;
      entry["name"] = selproduct.name;
      entry["product_type_id"] = selproduct.product_type_id;
      entry["product_type_name"] = selproduct.product_type_name;
      entry["wastage"] = selproduct.wastage;
      entry["productMaterialList"] = selproduct.productMaterialList;
      entry["materialcompositionname"] = selproduct.materialcompositionname;
      let prdexpobject:any = {...entry};



       


        selproduct.productStandardList.forEach(selstandard=>{
          let findproductEntries = curproductEntries.productStandardList.find(xx => xx.standard_id == selstandard.standard_id);

          
          if(findproductEntries===undefined){

            
            //IF not in current then add in product details


            prdexpobject["standard_id"] = selstandard.standard_id;
            prdexpobject["standard_name"] = selstandard.standard_name;//this.registrationForm.get('expname').value;
            prdexpobject["label_grade"] = selstandard.label_grade;
            prdexpobject["label_grade_name"] = selstandard.label_grade_name;
            prdexpobject["pdt_index"] =pdt_index;
            prdexpobject["productIndex"] = productIndex;
            
            this.productListDetails.push({...prdexpobject});


            let stdindex = this.productEntries[productIndex].productStandardList.findIndex(xx=>xx.standard_id == selstandard.standard_id);
            if(stdindex !== -1){
              this.productEntries[productIndex].productStandardList[stdindex].pdt_index = pdt_index;
            }

            pdt_index++;
            
          }else{
             
            let curdataentry:any = {};
            //If Standard Present in current list
            //selstandard -> new entry object

              
             curdataentry.id = prdexpobject.id;
             curdataentry.name = prdexpobject.name;
             curdataentry.product_type_id = prdexpobject.product_type_id;
             curdataentry.product_type_name = prdexpobject.product_type_name;
             curdataentry.wastage = prdexpobject.wastage;

             curdataentry.productMaterialList = [...prdexpobject.productMaterialList];
             curdataentry.materialcompositionname = prdexpobject.materialcompositionname;
 
            curdataentry.standard_id = selstandard.standard_id;
            curdataentry.standard_name = selstandard.standard_name;//this.registrationForm.get('expname').value;
            curdataentry.label_grade = selstandard.label_grade;
            curdataentry.label_grade_name = selstandard.label_grade_name;
            curdataentry.pdt_index =findproductEntries.pdt_index;
            curdataentry.productIndex = productIndex;
            let listdetailsindex = this.productListDetails.findIndex(s => s.pdt_index ==  findproductEntries.pdt_index);
             
            if(listdetailsindex === -1){
              this.productListDetails.push({...curdataentry});



              
              let stdindex = this.productEntries[productIndex].productStandardList.findIndex(xx=>xx.standard_id == selstandard.standard_id);
              if(stdindex !== -1){
                this.productEntries[productIndex].productStandardList[stdindex].pdt_index = pdt_index;
              }
               
              pdt_index++;
            }else{
              let pdtlistdetails = this.productListDetails[listdetailsindex];
              this.productListDetails[listdetailsindex] = {...curdataentry};


              //Update current product to all unitss..
              this.unitEntries.forEach((val,index)=>{
                if(!val.deleted){
                  let unitProductList = val.unitProductList;

                  
                  let unitlistpdtindex = unitProductList.findIndex(xp=> xp.pdt_index == findproductEntries.pdt_index );
                  if(unitlistpdtindex !== -1){
                    let pdtunitlistdetails = unitProductList[unitlistpdtindex];
                    unitProductList[unitlistpdtindex] = {...curdataentry,addition_type:pdtunitlistdetails.addition_type};
                  }
                  let expobject = {...val,unitProductList:unitProductList};
                  this.unitEntries[index] = expobject;
                }
              });
              

            }
            
          }
          
           
        })

        


        //when standard is removed remove from list details and unit entries

        curproductEntries.productStandardList.forEach(curselstandard=>{
          let findproductEntries = selproduct.productStandardList.find(xx => xx.standard_id == curselstandard.standard_id);
          
          if(findproductEntries===undefined){
            let listdetailsindex = this.productListDetails.findIndex(s => s.pdt_index ==  curselstandard.pdt_index);
            if(listdetailsindex !== -1){
              this.productListDetails.splice(listdetailsindex,1);


              this.unitEntries.forEach((val,index)=>{
                if(!val.deleted){
                  let unitProductList = val.unitProductList;
                  
                  
                  let unitlistpdtindex = unitProductList.findIndex(xp=> xp.pdt_index == curselstandard.pdt_index );
                  if(unitlistpdtindex !== -1){
                    unitProductList.splice(unitlistpdtindex,1);
                  }
                  let expobject = {...val,unitProductList:unitProductList};
                  this.unitEntries[index] = expobject;
                }
              });


            }
          }

        })
        

        
    }
     
      
  }

  addProductDetails(){
    let pdt_index =0;
    this.productListDetails=[];
    this.productEntries.forEach((selproduct)=>{
      let entry = [];
      entry["id"] = selproduct.id;
      entry["name"] = selproduct.name;
      entry["product_type_id"] = selproduct.product_type_id;
      entry["product_type_name"] = selproduct.product_type_name;
      entry["wastage"] = selproduct.wastage;
      entry["productMaterialList"] = selproduct.productMaterialList;
      entry["materialcompositionname"] = selproduct.materialcompositionname;
      let prdexpobject = {...entry};

      selproduct.productStandardList.forEach(selstandard=>{
        
        prdexpobject["standard_id"] = selstandard.standard_id;
        prdexpobject["standard_name"] = selstandard.standard_name;//this.registrationForm.get('expname').value;
        prdexpobject["label_grade"] = selstandard.label_grade;
        prdexpobject["label_grade_name"] = selstandard.label_grade_name;
        prdexpobject["pdt_index"] = pdt_index;
        pdt_index++;
        this.productListDetails.push({...prdexpobject});
         
      })
    })
  }
  editProduct(index:number){
    this.productIndex = index;
    
    let prd= this.productEntries[index];
    this.getProductTypeOnEdit(prd.id,prd.product_type_id);
    this.enquiryForm.patchValue({
      product: prd.id,
      wastage:prd.wastage,
      product_type:prd.product_type_id,
    });
    
     
    this.productStandardList = [...prd.productStandardList];
    this.productMaterialList = [...prd.productMaterialList];
    
    this.showProduct = true;


    this.unitIndex = null;
    this.showCert = false;
    
    
  }

  showProduct = false;
  showProductFn(){
    
    this.productIndex = null;
    this.productReset();
    if(this.showProduct){
      this.showProduct = false;
    }else{
      this.showProduct = true;
    }

    this.unitIndex = null;
    this.showCert = false;
    
  }


  
  /*
  Process Section
  */

  removeProcess(processId:number) {
    let index= this.processEntries.findIndex(s => s.id ==  processId);
    if(index !== -1)
      this.processEntries.splice(index,1);
  }
  addProcess(){
    let processId = this.enquiryForm.get('sel_process').value;
    let selprocess = this.processList.find(s => s.id ==  processId);
    this.processErrors = '';
    
    this.f.sel_process.setValidators([Validators.required]);
    this.f.sel_process.updateValueAndValidity();

    this.f.sel_process.markAsTouched();
    
    if(this.f.sel_process.errors){
      return false;
    }
    
    let entry= this.processEntries.find(s => s.id ==  processId);
    if(entry === undefined){
      let expobject:any=[];
      expobject["id"] = selprocess.id;
      expobject["addition_type"] = 1;
      expobject["name"] = selprocess.name;//this.registrationForm.get('expname').value;
      this.processEntries.push(expobject);
    }
    
    this.f.sel_process.setValidators(null);
    this.f.sel_process.updateValueAndValidity();

    this.enquiryForm.patchValue({
      sel_process: '',
    });
  }
  editProcess(processId:number){
    let prd= this.processEntries.find(s => s.id ==  processId);
    this.enquiryForm.patchValue({
      process: prd.id,
      wastage:prd.wastage
    });
  }
  /*
  Process Section Ends Here
  */


  /*
  Unit Product Section
  */
  unitProductList:any=[];
  removeUnitProduct(pdtindex:number) {
    //let index= this.unitProductList.findIndex(s => s.id ==  processId);
    //if(index != -1)
    this.unitProductList.splice(pdtindex,1);
  }
  addUnitProduct(){
 
    let unitProductIndex = this.enquiryForm.get('unit_product_id').value;

    let productListDetails:any;
    if(this.currentunittype==1){
      let appstandards = [...this.selStandardIds];
      if(appstandards.length>0){
        productListDetails = this.productListDetails.filter(x =>  appstandards.includes(""+x.standard_id+""));
      }
    }else{
      let appstandards = this.selUnitStandardList;
      if(appstandards.length>0){
        productListDetails = this.productListDetails.filter(x =>  appstandards.includes(""+x.standard_id+""));
      }
    }
    //let selunitproduct = {...this.productListDetails[unitProductIndex],'pdtListIndex':unitProductIndex};
    //let selunitproduct = {...productListDetails[unitProductIndex],'pdtListIndex':unitProductIndex};
    let selunitproduct = this.productListDetails.find(s => s.pdt_index ==  unitProductIndex); 

    this.f.unit_product_id.setValidators([Validators.required]);
    this.f.unit_product_id.updateValueAndValidity();
    this.f.unit_product_id.markAsTouched();
    
    if(this.f.unit_product_id.errors){
      return false;
    }
    
    this.unitproductErrors='';
    let entry= this.unitProductList.find(s => s.pdt_index ==  unitProductIndex);
    if(entry === undefined){
      //this.unitProductList.push(selunitproduct);
      this.unitProductList.push({...selunitproduct,addition_type:1});
    }
    
    this.f.unit_product_id.setValidators(null);
    this.f.unit_product_id.updateValueAndValidity();
    
    this.enquiryForm.patchValue({
      unit_product_id: '',
    });
  }


  selectedProductIds:any = [];
  onProductCheckboxChange(id: number, isChecked: boolean) {

    //const emailFormArray = <FormArray>this.myForm.controls.useremail;
    //const standardsFormArray = <FormArray>this.enquiryForm.get('company.standardsChk');
    
    if (isChecked) {
      this.selectedProductIds.push(id);
    } else {
      let index = this.selectedProductIds.findIndex(x => x == id);
      if(index!==-1 && this.selectedProductIds && this.selectedProductIds.length>0){
        this.selectedProductIds.splice(index,1);
      }
    }

  }
  
  filterProductStandard(stdId){
    const unitProductIndex = this.unitProductList.map(x=>x.pdt_index).map(String);
    
    return this.productListDetails.filter(x =>  stdId==x.standard_id && !unitProductIndex.includes(""+x.pdt_index+"")  );

    /*
    if(this.currentunittype==1){
     
      let appstandards = this.selStandardIds;//this.standardsChkDb.concat(this.enquiryForm.get('standardsChk').value);
      if(appstandards.length>0){
        //unitProductList
        
        //
        return this.productListDetails.filter(x =>  stdId==x.standard_id && !unitProductIndex.includes(""+x.pdt_index+"")  );
      }
    }else{
      let appstandards = this.selUnitStandardList;//this.standardsChkDb.concat(this.enquiryForm.get('standardsChk').value);
      if(appstandards.length>0){
        return this.productListDetails.filter(x =>  stdId==x.standard_id && !unitProductIndex.includes(""+x.pdt_index+"") );
      }
    }
    */
  }

  /*
  unitProductList
  Unit Product Section Ends Here
  */


  findLastIndex(array,searchterm) {
    let finalIndex = 1;
    if(array && array.length>0){
      let index = array.slice().reverse().findIndex(x => x.type==searchterm && x.deleted==0 && x.added ==1);
      let count = array.length - 1
      finalIndex = index >= 0 ? count - index : index;
    }
    
    return finalIndex;
  }
  /*
  Standard Section Starts
  */

  removeStandard(standardId:any) {
    let index= this.standardEntries.findIndex(s => s.id ==  standardId);
    if(index !== -1)
      this.standardEntries.splice(index,1);
     
  }
  license_number_error:any='';
  expiry_date_error:any = '';
  addStandard(){
    let standardId = this.enquiryForm.get('sel_standard').value;
    let license_number:any = this.enquiryForm.get('license_number').value;
    let expiry_date:any = this.enquiryForm.get('expiry_date').value;
    //let selstandard = this.standardList.find(s => s.id ==  standardId);
    let selstandard = this.reductionStandardList.find(s => s.id ==  standardId);
    this.unitstandard_error = '';
    this.addstandard_error = '';

    this.license_number_error = '';
    this.expiry_date_error = '';
    let stdadderror = false;
    if(standardId==''){
      this.unitstandard_error = 'Please select the standard';
      stdadderror = true;
      return false;
    }
    if(this.reductionStandardDetails.includes('license_number') && license_number==''){
      this.license_number_error = 'Please enter the license number';
      stdadderror = true;
      //return false;
    }
    if(this.reductionStandardDetails.includes('expiry_date') && expiry_date==''){
      this.expiry_date_error = 'Please enter the expiry date';
      stdadderror = true;
    }
    this.addstandard_error='';
    this.addauditstandard_error='';
    

    //if(this.uploadedFileNames)
    let curuploadfiles = this.uploadedFileNames.filter(val=>val.deleted ==0 && val.type=='cert');
    if(this.reductionStandardDetails.includes('certificate_file') && curuploadfiles.length <=0){
      this.addstandard_error = 'Please upload certification file';
      stdadderror = true;
    }
    let curaudituploadfiles = this.uploadedFileNames.filter(val=>val.deleted ==0 && val.type=='audit');
    if(this.reductionStandardDetails.includes('latest_audit_report') && curaudituploadfiles.length <=0){
      this.addauditstandard_error = 'Please upload latest audit report file';
      stdadderror = true;
    }
    if(stdadderror){
      return false;
    }
    
    
    let entry= this.standardEntries.findIndex(s => s.id ==  standardId);

    /*
    let certindex = localuploadedFileNames.findIndex(xx=>xx.type=='cert' && xx.deleted==0 && xx.added ==1);
    let auditindex = localuploadedFileNames.findIndex(xx=>xx.type=='audit' && xx.deleted==0 && xx.added ==1);
    if(certindex !== -1){
      this.uploadedFileNames[]
    }
    if(auditindex !== -1){
      
    }
    */
    let certIndex = this.findLastIndex(this.uploadedFileNames,'cert');
    let auditIndex = this.findLastIndex(this.uploadedFileNames,'audit');
    
    

    if(certIndex!==-1){
      if(this.uploadedFileNames[certIndex]){
        this.uploadedFileNames[certIndex].fileadded = 1;
      }
    }
    if(auditIndex!==-1){
      if(this.uploadedFileNames[auditIndex]){
        this.uploadedFileNames[auditIndex].fileadded = 1;
      }
    }
    /*
    let trackcertIndex = this.findLastIndex(this.upFilesTrack[this.unitIndex],'cert');
    let trackauditIndex = this.findLastIndex(this.upFilesTrack[this.unitIndex],'audit');
    
    if(trackcertIndex!==-1){
      if(this.upFilesTrack[this.unitIndex] && this.upFilesTrack[this.unitIndex][trackcertIndex]){
        this.upFilesTrack[this.unitIndex][trackcertIndex].fileadded = 1;
      }
    }
    if(trackauditIndex!==-1){
      if(this.upFilesTrack[this.unitIndex] && this.upFilesTrack[this.unitIndex][trackauditIndex]){
        this.upFilesTrack[this.unitIndex][trackauditIndex].fileadded = 1;
      }
    }
    */
    
    let expobject:any=[];
    expobject["id"] = selstandard.id;
    expobject["name"] = selstandard.name;//this.registrationForm.get('expname').value;
    expobject["uploadedFiles"] = [...this.uploadedFiles];
    expobject["uploadedFileNames"] = [...this.uploadedFileNames];
    expobject["license_number"] = license_number;
    expobject["expiry_date"] = expiry_date?this.errorSummary.displayDateFormat(expiry_date):'';

    //let entry= this.standardEntries.findIndex(s => s.id ==  standardId);
     
    if(entry === -1){
      this.standardEntries.push(expobject);
    }else{
      this.standardEntries[entry] = expobject;
    }
    this.reductionStandardDetails = [];
    this.uploadedFiles = [];
    this.uploadedFileNames=[];
    this.enquiryForm.patchValue({
      sel_standard: '',
      license_number: '',
      expiry_date: '',
    });
  }
  editStandard(standardId:any){

    let reductionStandardData = this.reductionStandardList.find(x=>x.id == standardId);
    if(reductionStandardData !== undefined){
      this.reductionStandardDetails = [...reductionStandardData.required_fields];
    }else{
      this.reductionStandardDetails = [];
    }
    
    let prd= this.standardEntries.find(s => s.id ==  standardId);
    this.uploadedFiles= [];
    this.uploadedFileNames = [];
   
    for (let i = 0; i < prd['uploadedFiles'].length; i++) {
      this.uploadedFiles.push(prd['uploadedFiles'][i]);
      this.uploadedFileNames.push({name:prd['uploadedFiles'][i].name,added:prd['uploadedFileNames'][i].added,deleted:prd['uploadedFileNames'][i].deleted,type:prd['uploadedFileNames'][i].type,fileadded:prd['uploadedFileNames'][i].fileadded});
      
    }
    /*
    this.uploadedFiles = [];
    this.uploadedFileNames=[];
    */

    this.enquiryForm.patchValue({
      sel_standard: prd.id,
      license_number: prd.license_number,
      expiry_date: prd.expiry_date?this.errorSummary.editDateFormat(prd.expiry_date):'',
    });
  }

  reductionStandardDetails:any = [];
  certifiedstandardChange(standardId:any){
    let reductionStandardData = this.reductionStandardList.find(x=>x.id == standardId);
    if(reductionStandardData !== undefined){
      this.reductionStandardDetails = [...reductionStandardData.required_fields];
    }else{
      this.reductionStandardDetails = [];
    }
    
    let entry= this.standardEntries.findIndex(s => s.id ==  standardId);
    //required_fields
    if(entry !== -1){
      this.editStandard(standardId);
    }else{
      this.enquiryForm.patchValue({
        license_number: '',
        expiry_date: '',
      });
      this.uploadedFiles = [];
      this.uploadedFileNames=[];
      this.addstandard_error='';
      this.addauditstandard_error='';
      this.license_number_error = '';
      this.expiry_date_error = '';
      this.unitstandard_error = '';
    }
  }
  
  /*
  Standard Section Ends Here
  */




  

  /*
  Unit Section Starts
  */
  filterProduct(){
    if(this.currentunittype==1){
     
      let appstandards = [...this.selStandardIds];//this.standardsChkDb.concat(this.enquiryForm.get('standardsChk').value);
      if(appstandards.length>0){
        return this.productListDetails.filter(x =>  appstandards.includes(""+x.standard_id+""));
      }
    }else{
      let appstandards = this.selUnitStandardList;//this.standardsChkDb.concat(this.enquiryForm.get('standardsChk').value);
      if(appstandards.length>0){
        return this.productListDetails.filter(x =>  appstandards.includes(""+x.standard_id+""));
      }
    }
  }

  filterStandard(){
    if(this.currentunittype==1){
      /*
      if(this.currentunittype==2){
        if(this.selUnitStandardList.length>0){
          return this.standardList.filter(x=> !(this.selUnitStandardList.includes(x.id)) );
        }
      }else if(this.currentunittype==1){
        */
      /*
      let appstandards = this.selStandardIds;//this.standardsChkDb.concat(this.enquiryForm.get('standardsChk').value);
      if(appstandards.length>0){
        return this.standardList.filter(x=> !(appstandards.includes(x.id)) );
      }
      */
      let appstandards = [...this.selStandardIds];//this.standardsChkDb.concat(this.enquiryForm.get('standardsChk').value);
      let selstdlist = this.standardList.filter(x=> appstandards.includes(x.id));
      if(appstandards.length>0){
        return this.reductionStandardList.filter(x=>{
          return selstdlist.findIndex(sx=>sx.code == x.code)===-1?true:false;
        });
      }
      //}
    }else{
      return this.reductionStandardList;
      //return this.standardList;
    }
  }
  removeUnit(index:number) {
    //let index= this.unitEntries.findIndex(s => s.id ==  unitId);
    //if(index != -1)
      //this.unitEntries.splice(index,1);

      this.unitEntries[index].deleted =1;
  }
  cancelUnit(){
    this.showCert = false;
    this.unitIndex = null;
    this.unitProductList = [];
    this.emptyUnits();
  }

  emptyUnits(){
    this.standardEntries = [];
    this.processEntries = [];
    this.uploadedFiles= [];
    this.uploadedFileNames = [];
    //this.processList = [];
    this.unitstandard_error = '';
    this.selUnitStandardList = [];
    
    this.appunitstandardErrors = '';
    this.company_unit_typeErrors = '';
    this.unitProductList = [];
    this.unit_autoid='';

    this.bsectorList=[];
    this.enquiryForm.patchValue({
      unit_name: '',
      unit_address: '',
      unit_zipcode: '',
      unit_country_id: '',
      unit_state_id: '',
      unit_city: '',
      no_of_employees: '',
      //unit_product_id: '',
      certFile:'',
      unit_id:'',
      business_sector_id:'',
      sel_standard:'',
      license_number:'',
      expiry_date:'',
      sel_reduction:'2'
    });


    this.f.unit_name.setValidators(null);
    this.f.unit_address.setValidators(null);
    this.f.unit_zipcode.setValidators(null);
    this.f.unit_country_id.setValidators(null);
    this.f.unit_state_id.setValidators(null);
    this.f.unit_city.setValidators(null);
    this.f.no_of_employees.setValidators(null);
    //this.f.unit_product_id.setValidators(null);
    this.f.business_sector_id.setValidators(null);

    this.f.unit_name.updateValueAndValidity();
    this.f.unit_address.updateValueAndValidity();
    this.f.unit_zipcode.updateValueAndValidity();
    this.f.unit_country_id.updateValueAndValidity();
    this.f.unit_state_id.updateValueAndValidity();
    this.f.unit_city.updateValueAndValidity();
    this.f.no_of_employees.updateValueAndValidity();
    //this.f.unit_product_id.updateValueAndValidity();
    this.f.business_sector_id.updateValueAndValidity();
  }

  touchUnit(){
    this.f.unit_name.markAsTouched();
    this.f.unit_address.markAsTouched();
    this.f.unit_zipcode.markAsTouched();
    this.f.unit_country_id.markAsTouched();
    this.f.unit_state_id.markAsTouched();
    this.f.unit_city.markAsTouched();
    this.f.no_of_employees.markAsTouched();
    //this.f.unit_product_id.markAsTouched();
    this.f.business_sector_id.markAsTouched();
  }
  unitproductErrors = '';
  addUnit(){
    
    /*const unit_nameval = this.enquiryForm.get('unit_name');
    unit_nameval.setValidators([Validators.required]);
    unit_nameval.updateValueAndValidity();
    return false;
    */
    this.f.unit_name.setValidators([Validators.required]);
    this.f.unit_address.setValidators([Validators.required]);
    this.f.unit_zipcode.setValidators([Validators.required]);
    this.f.unit_country_id.setValidators([Validators.required]);
    this.f.unit_state_id.setValidators([Validators.required]);
    this.f.unit_city.setValidators([Validators.required]);
    this.f.no_of_employees.setValidators([Validators.required,Validators.min(1),Validators.pattern('^[0-9]*$')]);
    this.f.business_sector_id.setValidators([Validators.required]);

    this.f.unit_name.updateValueAndValidity();
    this.f.unit_address.updateValueAndValidity();
    this.f.unit_zipcode.updateValueAndValidity();
    this.f.unit_country_id.updateValueAndValidity();
    this.f.unit_state_id.updateValueAndValidity();
    this.f.unit_city.updateValueAndValidity();
    this.f.no_of_employees.updateValueAndValidity();
    this.f.business_sector_id.updateValueAndValidity();

    let unit_id = this.f.unit_id.value;
    let unit_name = this.f.unit_name.value;
    let unit_address = this.f.unit_address.value;
    let unit_zipcode = this.f.unit_zipcode.value;
    let unit_country_id = this.f.unit_country_id.value;
    let unit_state_id = this.f.unit_state_id.value;
    let unit_city = this.f.unit_city.value;
    let no_of_employees = this.f.no_of_employees.value;
    let business_sector_id = this.f.business_sector_id.value;
    
    let sel_reduction = this.f.sel_reduction.value;
 
    this.touchUnit();

   // if(this.standardEntries.length <=0){
    //  this.unitstandard_error = 'Please add standard';
    //}
    if(this.processEntries.length <=0){
      this.processErrors = 'Please add process';
    }
    //appunitstandardErrors
    this.appunitstandardErrors = '';
    if(this.selUnitStandardList.length<=0 && this.currentunittype!=1){
      this.appunitstandardErrors = 'Please select the standard';
    }
    
    this.company_unit_typeErrors = '';
    if(!this.currentunittype){
      this.company_unit_typeErrors = 'Please select facility type';
    }
    this.unitproductErrors = '';
     
    if(this.unitProductList.length <=0){
      this.unitproductErrors = 'Please add product';
    }

    //business_sector_id
    //this.standardwiseBSector 
    let notExistName:any = [];
    this.business_sector_idErrors = '';
    //if(this.currentunittype!=1){
      let curStdList:any = [];
      if(this.currentunittype!=1){
        curStdList = [...this.selUnitStandardList].map(String);
      }else{
        this.selStandardList.forEach(cs=>{
          curStdList.push(cs.id);
        });
      }
      
      
      if(curStdList.length > 0){
        curStdList.forEach(selstdID=>{
          let bsectorexistsforStd:any = 0;
          if(this.standardwiseBSector[selstdID] && this.standardwiseBSector[selstdID].length>0){
            if(business_sector_id && business_sector_id.length>0){
              let business_sector_idchk:any = business_sector_id.map(String);
              business_sector_idchk.forEach(bid=>{
                if(this.standardwiseBSector[selstdID].includes(bid)){
                  bsectorexistsforStd = 1;
                }
              })
            }
            
          }
          if(!bsectorexistsforStd){
            //selstdID
            notExistName.push(this.standardList.find(xx=>xx.id==selstdID).code);
          }
        })
      }
   //}
    if(notExistName.length>0){
      this.business_sector_idErrors = "Please add business sector for: " + notExistName.join(', ');
    }
    //

    if( this.business_sector_idErrors || this.unitproductErrors || unit_name =='' || unit_address=='' || unit_zipcode=='' || unit_country_id=='' || unit_state_id=='' || unit_city=='' || no_of_employees=='' || business_sector_id=='' || this.unitstandard_error!='' || this.processErrors!='' || this.f.no_of_employees.errors || this.appunitstandardErrors != ''
    || this.company_unit_typeErrors !=''
    ){
      return false;
    }
    
    //let selunit = this.unitList.find(s => s.id ==  unitId);
    this.unitErrors = '';
    /*
    if(unitId==''){
      this.unitErrors = 'Please select the unit';
      return false;
    }
    */
    
    
    
     let countrysel = this.countryList.find(s => s.id ==  unit_country_id);
     let statesel = this.unitStateList.find(s => s.id ==  unit_state_id);
    
    
    
      let expobject:Units;
      expobject = {
        "unit_id" : unit_id,
        "addition_type" : this.addition_type,
        "unit_type":this.currentunittype,
        "unit_name": unit_name,
        "unit_address":unit_address,
        "unit_zipcode":unit_zipcode,
        "unit_country_id":unit_country_id,
        "unit_state_id":unit_state_id,
        "business_sector_id":business_sector_id,
        "bsectorList":this.bsectorList,
        "unitProductList":this.unitProductList,
        "unit_country_name":countrysel.name,
        "unit_state_name":statesel.name,
        "unit_city":unit_city,
        "no_of_employees":no_of_employees,
        "sel_reduction":sel_reduction,
        "sel_standard":sel_reduction==1?this.standardEntries:[],
        "sel_process":this.processEntries,
        "unitStateList":this.unitStateList,
        "selUnitStandardList":this.selUnitStandardList,
        deleted:0
        //"processList":this.processList
        //"certFile" : this.uploadedFiles
      }
      
      //unitIndex
      if(this.unitIndex!=null && this.unitIndex!=undefined && this.unitIndex>=0){
        let existunitdata:any = this.unitEntries[this.unitIndex];
        expobject.unit_exists = existunitdata.unit_exists;
        expobject.business_sector_exists = existunitdata.business_sector_exists;
        expobject.sel_processexists = existunitdata.sel_processexists;
        


        this.unitEntries[this.unitIndex] = expobject;
      }else{
        expobject["addition_type"] = 1;
        this.unitEntries.push(expobject);
      }
      //this.uploadedFiles.push(files[i]);
    
      this.emptyUnits();
      this.unitIndex = null;
      this.showCert = false;
  }
  
  unit_autoid:any;
  editUnit(index:number){
    this.unitIndex = index;

    this.showCert = true;
    let unit_data = this.unitEntries[index];
    this.unit_autoid = this.unitEntries[index].unit_id;

    this.processEntries = [...unit_data['sel_process']];
    this.standardEntries = [...unit_data['sel_standard']];
    this.unitStateList = [...unit_data['unitStateList']];
    let selUnitStandardList = unit_data['selUnitStandardList'].map(String);
    this.selUnitStandardList = selUnitStandardList;//['1','3'];//[...unit_data['selUnitStandardList']];
    this.unitProductList = [...unit_data['unitProductList']];
     
   // let bsectorVal= qualificationChecklist.business_sector_id.map(String);
    this.bsectorList = [...unit_data['bsectorList']];
    //this.processList = [...unit_data['processList']];


    this.currentunittype = unit_data['unit_type'];

    this.addition_type = unit_data['addition_type'];
    
    this.setfacilityunit('',unit_data['unit_type']);
    let sel_reduction = '2';
    if(this.standardEntries.length>0){
      sel_reduction ='1';
    }
    this.enquiryForm.patchValue({
      unit_id: unit_data['unit_id'],
      unit_name: unit_data['unit_name'],
      unit_address: unit_data['unit_address'],
      unit_zipcode: unit_data['unit_zipcode'],
      unit_country_id: unit_data['unit_country_id'],
      unit_state_id: unit_data['unit_state_id'],
      unit_city: unit_data['unit_city'],
      no_of_employees: unit_data['no_of_employees'],
      business_sector_id: unit_data['business_sector_id'].map(String),
      sel_reduction:sel_reduction
    });
    this.reductionStandardDetails = [];
  }
  
  /*
   let formData = new FormData();
  for (var i = 0; i < this.uploadedFiles.length; i++) {
      formData.append("uploads[]", this.uploadedFiles[i], this.uploadedFiles[i].name);
  }
  */
  uploadedFiles:any = [];
  uploadedFileNames:any=[];
  addauditstandard_error = '';

  removeFiles(index,type){
    //this.uploadedFiles.splice(index, 1);
    //this.uploadedFileNames.splice(index, 1);
    //let uploadfile = this.uploadedFileNames.filter((x,i)=>x.deleted==0 && x.type==type);

    let filenames =  this.uploadedFileNames.map(x => {
      //return num * 2;
      if(x.deleted==0 && x.type==type){
        x.deleted=1;
      }
      return x;
    });
    
    this.uploadedFileNames = filenames;
    /*
    uploadfile[index].added=0;
    uploadfile[index].deleted=1;
    uploadfile[index].type=type;
    */
    this.addstandard_error = '';
    this.addauditstandard_error = '';

    /*
    let trackcertIndex = this.findLastIndex(this.upFilesTrack[this.unitIndex],type);
    if(trackcertIndex!==-1){
      if(this.upFilesTrack[this.unitIndex] && this.upFilesTrack[this.unitIndex][trackcertIndex]){
        this.upFilesTrack[this.unitIndex][trackcertIndex].deleted = 1;
      }
    }
    */
    

  }
  get filterFile(){
    return this.uploadedFileNames.filter(x=>x.deleted==0 && x.type=='cert');
  }
  get auditfilterFile(){
    return this.uploadedFileNames.filter(x=>x.deleted==0 && x.type=='audit');
  }
  get standardFiles(){
    return this.uploadedFileNames.filter(x=>x.deleted==0);
  }
  filterItemsOfType(uploadedfiles){
     if(uploadedfiles){
      return uploadedfiles.filter(x=>x.deleted==0);
    }else{
      return '';
    }
  }
  removecompanyFile(){
    this.company_file = '';
    this.formData.delete('company_file');
  }
  companyfileChange(element) {
    let files = element.target.files;
    this.companyFileError ='';
    let fileextension = files[0].name.split('.').pop();
    if(this.errorSummary.checkValidDocs(fileextension))
    {

      this.formData.append("company_file", files[0], files[0].name);
      this.company_file = files[0].name;
      
    }else{
      this.companyFileError ='Please upload valid file';
    }
    element.target.value = '';
   
  }

  removebrandFile(){
    this.brand_file = '';
    this.formData.delete('brand_file');
  }
  
  brandFileError ='';
  brandfileChange(element) {
    let files = element.target.files;
    this.brandFileError ='';
    let fileextension = files[0].name.split('.').pop();
    if(this.errorSummary.checkValidDocs(fileextension))
    {

      this.formData.append("brand_file", files[0], files[0].name);
      this.brand_file = files[0].name;
      
    }else{
      this.brandFileError ='Please upload valid file';
    }
    element.target.value = '';
   
  }

  upFilesTrack:any = [];
  fileChange(element,type) {
    //let filess = element.target.files;
    //this.uploadedFiles.push(element.target.files);
    let standardId = this.enquiryForm.get('sel_standard').value;
    if(standardId ==''){
      this.unitstandard_error='Please select standard to upload files';
      element.target.value = '';
      return false;
    }
    this.unitstandard_error='';
    this.addstandard_error='';
    this.addauditstandard_error='';

    let filesadded = this.uploadedFileNames.filter(x=>x.deleted==0 && x.type==type);
    if(filesadded.length>0){

      if(type == 'cert'){
        this.addstandard_error='Please remove the file to add new files';
        element.target.value = '';
        return false;
      }
      if(type == 'audit'){
        this.addauditstandard_error='Please remove the file to add new files';
        element.target.value = '';
        return false;
      }
    }

    let files = element.target.files;
    for (let i = 0; i < files.length; i++) {
      
      let fileextension = files[i].name.split('.').pop();
      if(this.errorSummary.checkValidDocs(fileextension))
      {
        this.uploadedFiles.push(files[i]);
        this.uploadedFileNames.push({name:files[i].name,added:1,deleted:0,type,fileadded:0});
      }else{
        if(type == 'cert'){
          this.addstandard_error='Please upload valid file';
          element.target.value = '';
          return false;
        }
        if(type == 'audit'){
          this.addauditstandard_error='Please upload valid file';
          element.target.value = '';
          return false;
        }
        return false;
      }
      
    }
    /*
    
    for (let i = 0; i < this.uploadedFiles.length; i++) {
      this.uploadedFileNames.push({'name':this.uploadedFiles[i].name});
    }
    */
    
    let unitValIndex=0;
    if(this.unitIndex >=0 && this.unitIndex !==null){
      unitValIndex = this.unitIndex;
    }else{
      unitValIndex = this.unitEntries.length;
    }
    
    for (let i = 0; i < files.length; i++) {
      this.formData.append("uploads["+unitValIndex+"]["+standardId+"]["+type+"]", files[i], files[i].name);
      /*
      if(!this.upFilesTrack[unitValIndex]){
        this.upFilesTrack[unitValIndex] = [];
      }
      let curlength = this.upFilesTrack[unitValIndex].filter(xf => xf.unitValIndex== unitValIndex && xf.type==type && xf.standardId == standardId).length;
      this.upFilesTrack[unitValIndex].push({unitValIndex,standardId,type,added:1,deleted:0,fileadded:0,curIndex:curlength});
      */
    }
    element.target.value = '';
  }
  

   public onFileSelected(event: EventEmitter<File[]>) {
    const file: File = event[0];

    //let uploadedFiles = element.target.files;
    //this.uploadedFiles[i], this.uploadedFiles[i].name
    

    readBase64(file)
      .then(function(data) {
    })

  }
   
  /*
  unit Section Ends Here
  */





  
  getStateList(id:number,stateid){
    
	if(stateid =='unit_state_id'){
       this.unitStateList = [];
	   this.enquiryForm.patchValue({unit_state_id:''});
    }else{
       this.stateList = [];
	   this.enquiryForm.patchValue({state_id:''});
    }
	  
    if(id){
      if(stateid =='unit_state_id'){
        this.loading['unitstate'] = 1;
      }else{
        this.loading['state'] = 1;
      }
      
      this.countryservice.getStates(id).pipe(first()).subscribe(res => {
        if(stateid =='unit_state_id'){
          this.unitStateList = res['data'];
          this.loading['unitstate'] = 0;
        }else{
          this.stateList = res['data'];
          this.loading['state'] = 0;
        }
        
      });
    }
  }

  get f() { return this.enquiryForm.controls; }

  
  
  validateAllFormFields(formGroup: FormGroup) {         //{1}
    Object.keys(formGroup.controls).forEach(field => {  //{2}
      const control = formGroup.get(field);             //{3}
      if (control instanceof FormControl) {             //{4}
        control.markAsTouched({ onlySelf: true });
      } else if (control instanceof FormGroup) {        //{5}
        this.validateAllFormFields(control);            //{6}
      }
    });
  }

  


  selStandardList:Array<any> = [];
  selUnitStandardList:Array<any> = [];

  selStandardIds:Array<any> = [];

  onChange(id: number, isChecked: boolean) {

    //const emailFormArray = <FormArray>this.myForm.controls.useremail;
    //const standardsFormArray = <FormArray>this.enquiryForm.get('company.standardsChk');
	
  	this.labelGradeList = [];	
  	this.enquiryForm.patchValue({composition_standard:'',label_grade:''});

    //standardList
    this.appstandardErrors = '';
    let standardDetails = this.standardList.find(x => x.id == id);
    
    const standardsFormArray = <FormArray>this.enquiryForm.get('standardsChk');
    if (isChecked) {
      this.selStandardIds.push(""+id+"");
      standardsFormArray.push(new FormControl(id));
      this.selStandardList.push({id:standardDetails.id,name:standardDetails.name});
      
      
      let reductionStandardListData = this.reductionStandardList.find(xstd=> xstd.code == standardDetails.code);
      
      if(this.unitEntries && this.unitEntries[0]){
        let sel_standard_data:any = this.unitEntries[0].sel_standard;
        if(sel_standard_data && sel_standard_data.length>0 && reductionStandardListData){
          let standardaddIndex = sel_standard_data.findIndex(x => ""+x.id+"" == ""+reductionStandardListData.id+"");
          if(standardaddIndex!==-1){
            this.unitEntries[0].sel_standard.splice(standardaddIndex,1);
          }
        }
      }
      
      this.getBsectorListAllUnits('1');

     
    } else {

      

      let indexsel = this.selStandardIds.findIndex(x => x == ""+id+"");
      if(indexsel!==-1){
        this.selStandardIds.splice(indexsel,1);
      }
        
      let index = standardsFormArray.controls.findIndex(x => x.value == id);
      if(index!==-1){
        standardsFormArray.removeAt(index);
      }

      this.selStandardList = this.selStandardList.filter(x => x.id != id);
      //this.selStandardList.removeAt(index);
      this.emptySelectedProductDetails(id);
      
    }
    this.standardsLength = this.enquiryForm.get('standardsChk').value.length;
    //this.emptyProductUnitDetails();
	  this.std_with_product_std_error='';
    
    this.unitIndex = null;
    this.showCert = false;
  }

  standardwiseBSector:any = [];
  business_sector_idErrors:any = '';
  getBSectorStandardWise(){
    /*
    let standardvals:any = [];
    this.selStandardList.forEach(val=>{
      standardvals.push(val.id);
    })
    */
    this.enquiry.getBSectorStandardWise({}).subscribe(res => {
      this.standardwiseBSector = res;
       
    });
  }

  emptySelectedProductDetails(stdid){
    
    let productEntries = [...this.productEntries];

    let remindex:any = [];
    productEntries.forEach((prd,index)=>{
      let selstandard = prd.productStandardList.find(xx=>xx.standard_id == stdid);
       
      if(selstandard !== undefined && prd.productStandardList.length>1){



        prd.productStandardList.forEach((selstandard,pdtindex)=>{
          if(selstandard.standard_id==stdid){

           

            let listdetailsindex = this.productListDetails.findIndex(s => s.pdt_index ==  selstandard.pdt_index);
            if(listdetailsindex !== -1){
              this.productListDetails.splice(listdetailsindex,1);


              this.unitEntries.forEach((val,index)=>{
                if(!val.deleted){
                  let unitProductList = val.unitProductList;
                  

                  let unitlistpdtindex = unitProductList.findIndex(xp=> xp.pdt_index == selstandard.pdt_index );
                  if(unitlistpdtindex !== -1){
                    unitProductList.splice(unitlistpdtindex,1);
                  }
                  
                  
                  let expobject = {...val,unitProductList:unitProductList};
                  
                
                  
                  this.unitEntries[index] = expobject;
                }
              });

            }

            this.productEntries[index].productStandardList.splice(pdtindex,1);
          }
        

        })


       
      }else if(selstandard !== undefined && prd.productStandardList.length==1){
        //this.removeProduct(index);
        remindex.push(index);
      }
      
    })

    remindex.reverse();
    remindex.forEach(indexremove=>{
      this.removeProduct(indexremove);
    })


     this.unitEntries.forEach((val,index)=>{
      if(!val.deleted){
        //let sel_standard = val.sel_standard;


        
       // let unitstdindex = sel_standard.findIndex(xc=>xc.id==stdid);
        //if(unitstdindex!==-1){
        //  sel_standard.splice(unitstdindex,1);
        //}
        
        let selUnitStandardList:any = val.selUnitStandardList;
        
        let unitstdidindex = selUnitStandardList.findIndex(xc=>xc==stdid);
        if(unitstdidindex!==-1){
          selUnitStandardList.splice(unitstdidindex,1);
        }
        
         

        let expobject:any;
        if(val.unit_type==1){
          //let sel_standard = [...this.selStandardIds];
          //expobject = {...val,sel_standard:sel_standard};
          expobject = {...val};
         
        }else{
          //expobject = {...val,sel_standard:sel_standard,selUnitStandardList:selUnitStandardList};
         expobject = {...val,selUnitStandardList:selUnitStandardList};
        }
        
        this.unitEntries[index] = expobject;
      }
      });
      this.getBsectorListAllUnits();
  }

  getBsectorListAllUnits(formainonly=''){
    
    
    
    this.unitEntries.forEach((val,index)=>{
      let checkunit =1;
      if(formainonly){
        if(val.unit_type ==1){
          checkunit = 1;
        }else{
          checkunit = 0;
        }
      }
      if(!val.deleted && checkunit){
      let standardvals=[];
       
      if(val.unit_type ==1){
         
        this.selStandardList.forEach(val=>{
          standardvals.push(val.id);
        })
      }else{
        standardvals=  [...val.selUnitStandardList];
      }



      if(standardvals && standardvals.length>0)
      {
        this.BusinessSectorService.getBusinessSectorsbystds({standardvals}).subscribe(res => {
          let bsectorList = res['bsectors'];
          let unit_id = val.unit_id;
          let business_sector_id = val.business_sector_id;
          
          let removebsindex=[];
          business_sector_id.forEach((bsl,index)=>{
            let bsindex = bsectorList.findIndex(x => x.id == bsl);
            if(bsindex === -1){
              removebsindex.push(index);
            }
          })

          removebsindex.reverse();
          removebsindex.forEach(indexrem=>{
            business_sector_id.splice(indexrem,1);
          })
          let expobject = {...val,bsectorList:bsectorList,business_sector_id:business_sector_id};
          this.unitEntries[index] = expobject;
        }); 
      }else{    
        let bsectorList = [];
        let business_sector_id=[];
        //this.enquiryForm.patchValue({business_sector_id:''});   
        //if(this.unitEntries[0] !== undefined){
        //  this.unitEntries[0]['bsectorList'] = [];
        //}

        let expobject = {...val,bsectorList:bsectorList,business_sector_id:business_sector_id};
        this.unitEntries[index] = expobject;
      }

    }

    });
    
    
    
  }

  emptyProductUnitDetails(){
    this.productEntries = [];
    this.productListDetails = [];
    //this.productEntries = [];
    if(this.unitEntries.length>0){
      this.unitEntries.forEach((element,index) => {
        if(!element.deleted){
        let expobject:Units;
          
          expobject = {
            "unit_id" : element.unit_id,
            "unit_type":element.unit_type,
            "unit_name": element.unit_name,
            "unit_address":element.unit_address,
            "unit_zipcode":element.unit_zipcode,
            "unit_country_id":element.unit_country_id,
            "unit_state_id":element.unit_state_id,
            "business_sector_id":[],
            "bsectorList":[],
            "unitProductList":[],
            "unit_country_name":element.unit_country_name,
            "unit_state_name":element.unit_state_name,
            "unit_city":element.unit_city,
            "no_of_employees":element.no_of_employees,          
            "sel_standard":[],
            "sel_process":element.sel_process,
            "unitStateList":element.unitStateList,
            "selUnitStandardList":[]
            //"processList":[]
            //"certFile" : this.uploadedFiles
          }
          this.unitEntries[index] = expobject;
        }
      });
      
    }
  }

  emptyUnitPrdStd(){
    this.unitProductList = [];
    //this.processEntries = [];
  }

  onUnitStandardChange(id: number, isChecked: boolean) {
    
    let standardDetails = this.selStandardList.find(x => x.id == id);
    
    const standardsFormArray = <FormArray>this.enquiryForm.get('unitstandardsChk');
    if (isChecked) {
      standardsFormArray.push(new FormControl(id));
      this.selUnitStandardList.push(standardDetails.id);
    } else {
      let index = standardsFormArray.controls.findIndex(x => x.value == id);
      if(index!==-1){
        standardsFormArray.removeAt(index);
      }

      this.selUnitStandardList = this.selUnitStandardList.filter(x => x != id);
      this.emptyUnitParticularPrdStd(id);
    }
    
    //this.emptyUnitPrdStd();
    //this.standardsLength = this.enquiryForm.get('unitstandardsChk').value.length;
  }

  emptyUnitParticularPrdStd(stdid){


    //this.unitEntries.forEach((val,index)=>{

      let val = this.unitEntries[this.unitIndex];

      let unitProductList:any;
      if(this.unitProductList){
        unitProductList = this.unitProductList;
      }else if(val){
        unitProductList = val.unitProductList;
        
      }
      /*
      if(val){
        unitProductList = val.unitProductList;
      }else{
        unitProductList = this.unitProductList;
      }
      */
       
      if(unitProductList && unitProductList.length>0){
        let rempdtlist:any = [];
        unitProductList.forEach((pp,stdpdtindex)=>{
           if(pp.standard_id==stdid){
            //unitProductList.splice(stdpdtindex,1);
            rempdtlist.push(stdpdtindex);
           }
          
          
        });
        rempdtlist.reverse();
        if(rempdtlist.length>0){
            rempdtlist.forEach(pdtindex=>{
              unitProductList.splice(pdtindex,1);
            });
        }
        this.unitProductList = [...unitProductList];
        if(val){
          let expobject = {...val,unitProductList:unitProductList};
          this.unitEntries[this.unitIndex] = expobject;
        }
      }


    //});

  }
  
  std_with_product_std_error='';
  validateProductWithStandard()
  {
	 this.appstandardErrors = ''; 
	 // --------  Standard Addition Code Start Here ---------	
	 if(this.selStandardList.length>0)
	 {
		if(this.standard_addition_id!==null && this.standard_addition_id>0)
		{
			let selectedStdCount=0;
			this.selStandardIds.forEach((val)=>{
				if(this.standardAdditionList.includes(val))
				{
					selectedStdCount = selectedStdCount+1;	
				}
			});			
			
			if(selectedStdCount==0 || (this.standardAdditionList.length>0 && selectedStdCount>0 && this.standardAdditionList.length!=selectedStdCount))
			{
				this.appstandardErrors = 'Please select standards';
				return false;				
			}						
		}
	  }		
	  // --------  Standard Addition Code End Here ---------
	  
	  
	  let selStandardListLength = this.selStandardList.length;
	  let selectedProductStd=[];
	  this.productEntries.forEach((selproduct)=>{
      selproduct.productStandardList.forEach(selstandard=>{
        if(selectedProductStd.includes(parseInt(""+selstandard.standard_id)) === false){
          selectedProductStd.push(parseInt(""+selstandard.standard_id));
        }
  		});
	  });
    
    let selectedPrdStdLength = selectedProductStd.length;
    this.std_with_product_std_error = '';
	  if(selStandardListLength<=0 && selectedPrdStdLength<=0){
		this.std_with_product_std_error = 'Please select standard(s) and add product(s).';
		return false;  		  
	  }else if(selStandardListLength<=0){
		this.std_with_product_std_error = 'Please select standard(s).';
		return false;		  
	  }else if(selectedPrdStdLength<=0){
		this.std_with_product_std_error = 'Please add product(s).';
		return false;		  
	  }else if(selStandardListLength>0 && selectedPrdStdLength>0 && selStandardListLength!=selectedPrdStdLength){
		this.std_with_product_std_error = 'Please add product for all selected application standard(s).';
		return false;
	  }	  
	  return true;
  }

  showCertFn(){
  	if(!this.validateProductWithStandard())
  	{
  		return false;
  	}
    this.unitIndex = null;
    this.unitListError='';
    let bsectorList= this.bsectorList;

    this.emptyUnits();
    if(this.showCert){
      this.showCert = false;
    }else{
      this.showCert = true;
    }
    this.enquiryForm.patchValue({
      sel_reduction : "2",
    });

    if(this.unitEntries.length==0){
      this.unitStateList = this.stateList;
      this.bsectorList=bsectorList;
      this.enquiryForm.patchValue({
        unit_name: this.f.company_name.value,
        unit_address:this.f.company_address.value,
        unit_zipcode:this.f.zipcode.value,
        unit_city:this.f.city.value,
        unit_country_id:this.f.country_id.value!=null && this.f.country_id.value!=''?this.f.country_id.value:'',
        unit_state_id:this.f.state_id.value!=null && this.f.state_id.value!=''?this.f.state_id.value:''
      });
      this.currentunittype = 1;
      this.unitypename = 'Scope Holder';
    }else{
      this.currentunittype = 0;
      this.bsectorList=[];
      this.addition_type = 1;
    }
    this.setfacilityunit('', this.currentunittype);
  }
  unitypename = '';
  setfacilityunit(event,value){
    this.currentunittype = value;
    if(value==2){
      this.unitypename = 'Facility';
    }else if(value==3){
      this.unitypename = 'Subcontractor';
    }else if(value==1){
      this.unitypename = 'Scope Holder';
    }else{
      this.unitypename = '';
    }
  }
  currentunittype:number=0;
  fillUnit(){
    //this.currentunittype = value;
    //let checkedoption = event.currentTarget.checked;
    if(this.currentunittype == 1){
      
      this.unitStateList = this.stateList;
      this.enquiryForm.patchValue({
        unit_name: this.f.company_name.value,
        unit_address:this.f.company_address.value,                                                                                                                                                                    
        unit_zipcode:this.f.zipcode.value,
        unit_city:this.f.city.value,
        unit_country_id:this.f.country_id.value,
        unit_state_id:this.f.state_id.value,
       
      });
    }
    /*else if(checkedoption == false){
                                                     ;
      this.unitStateList = [];
      this.enquiryForm.patchValue({
        unit_name: '',
        unit_address:'',
        unit_zipcode:'',
        unit_city:'',
        unit_country_id:'',
        unit_state_id:'',
        
      });

    }
    */
  }

  fnValidateCertificationBody(){
    let formerrors = false;
    if(this.ceritifedByOtherCertificationBodyFormStatus)
    {
      this.standardList.forEach((x,index)=>{
        if(this.selStandardIds.includes(x.id))
        {			
          let validity_date = this.ocb.at(index).value.validity_date;
          let certification_body = this.ocb.at(index).value.certification_body;
          let document = this.certifiedbyothercbfiles[x.id];
          
          this.ocb.at(index).get("validity_date").setValidators([]);
          this.ocb.at(index).get("validity_date").updateValueAndValidity();
          this.ocb.at(index).get("validity_date").markAsTouched();

          this.ocb.at(index).get("certification_body").setValidators([]);
          this.ocb.at(index).get("certification_body").updateValueAndValidity();
          this.ocb.at(index).get("certification_body").markAsTouched();
          this.certifiedbyothercbFileError[x.id] = '';
          //console.log(certification_body);

          if((validity_date!='' && validity_date!==null) || (certification_body!='' && certification_body!==null) || (this.certifiedbyothercbfiles[x.id] && this.certifiedbyothercbfiles[x.id] !=''  && this.certifiedbyothercbfiles[x.id] !==undefined))
          {
            this.ocb.at(index).get("validity_date").setValidators([Validators.required]);
            this.ocb.at(index).get("validity_date").updateValueAndValidity();
            this.ocb.at(index).get("validity_date").markAsTouched();

            this.ocb.at(index).get("certification_body").setValidators([Validators.required]);
            this.ocb.at(index).get("certification_body").updateValueAndValidity();
            this.ocb.at(index).get("certification_body").markAsTouched();

            if(!this.certifiedbyothercbfiles[x.id] || this.certifiedbyothercbfiles[x.id] =='' || this.certifiedbyothercbfiles[x.id] === undefined)
            {
              this.certifiedbyothercbFileError[x.id] = 'Please upload file';
              formerrors = true;
            }
            if(validity_date=='' || validity_date===null){
              formerrors = true;
            }
            if(certification_body=='' || certification_body===null){
              formerrors = true;
            }
          }
        
        }	
      });
    }	
    return formerrors;
  }
  success:any;
  companyFileError ='';
  unitListError = '';
  appstandardErrors = '';
  appunitstandardErrors = '';

  unitprocessErrors='';
  unitstandardErrors = '';
  openmodal(content,arg='') {
    this.modalss = this.modalService.open(content, {ariaLabelledBy: 'modal-basic-title',centered: true});
  }

  DownloadFile(val,filename)
  {
    this.loadingFile  = true;
    this.brandService.downloadFile(val)
     .pipe(first())
     .subscribe(res => {
      this.loadingFile = false;
      this.modalss.close();
      let fileextension = filename.split('.').pop(); 
      let contenttype = this.errorSummary.getContentType(filename);
      saveAs(new Blob([res],{type:contenttype}),filename);
    },
    error => {
      this.error = error;
      this.loadingFile = false;
      this.modalss.close();
    });
  }

  onSubmit(actiontype){
    
    if(!this.validateProductWithStandard())
  	{
  		this.error = {summary:this.std_with_product_std_error};
  		return false;
    }
    
	   
     
	  this.companyFileError ='';
    this.brandFileError='';
    this.unitListError = '';
    this.appstandardErrors = '';
    this.productstandard_error = '';
	  this.std_with_product_std_error = '';

    if(!this.showCert){
      this.f.unit_name.setValidators(null);
      this.f.unit_name.updateValueAndValidity();
    }else{
      this.f.unit_name.setValidators([Validators.required]);
      this.f.unit_name.updateValueAndValidity();
      this.touchUnit();
    }
    /*
    this.uploader.onBuildItemForm = (item, form) => {
      form.append('productsWastage', this.productEntries);
      form.append('unitsLists', this.unitEntries);
    };
    */
    this.f.product.setValidators([]);
    this.f.wastage.setValidators([]);
    this.f.product_type.setValidators([]);
    this.f.composition_standard.setValidators([]);
    this.f.label_grade.setValidators([]);
    this.f.material.setValidators([]);
    this.f.material_type.setValidators([]);
    this.f.material_percentage.setValidators([]);
    this.f.sel_process.setValidators([]);
    
    this.f.product.updateValueAndValidity();
    this.f.wastage.updateValueAndValidity();
    this.f.product_type.updateValueAndValidity();
    this.f.composition_standard.updateValueAndValidity();
    this.f.label_grade.updateValueAndValidity();
    this.f.material.updateValueAndValidity();
    this.f.material_type.updateValueAndValidity();
    this.f.material_percentage.updateValueAndValidity();
    this.f.sel_process.updateValueAndValidity();
    

    this.f.unit_name.updateValueAndValidity();

    this.validateAllFormFields(this.enquiryForm);

    if(this.company_file ==''){
      //this.companyFileError ='Please upload company certification file';
    }

    //this.standardsLength = this.standardsChkDb.concat(this.enquiryForm.get('standardsChk').value).length; //this.enquiryForm.get('standardsChk').value.length;
    let standards=[];
    this.selStandardList.forEach(val=>{
      standards.push(val.id);
    })
    this.standardsLength = standards.length;
    
    this.submitted = 1;
    this.submittedError = 0;

    let company_name = this.f.company_name.value;
    let company_address = this.f.company_address.value;
    let zipcode = this.f.zipcode.value;
    let country_id = this.f.country_id.value;
    let state_id = this.f.state_id.value;
    let city = this.f.city.value;
    let tax_no = this.f.tax_no.value;
    let company_file = this.company_file;
    let salutation = this.f.salutation.value;
    let first_name = this.f.first_name.value;
    let last_name = this.f.last_name.value;
    let job_title = this.f.job_title.value;
    let company_telephone = this.f.company_telephone.value;
    let company_email = this.f.company_email.value;
    let sel_brand_ch = this.f.sel_brand_ch.value;

    let formerrors=false;
    let certbodyformerrors = false;
    certbodyformerrors = this.fnValidateCertificationBody();
    if(certbodyformerrors == true){
      formerrors = certbodyformerrors;
    }

    this.enquiryForm.markAllAsTouched();
    this.appchecklist.forEach((x,index)=>{
      let answer = this.t.at(index).value.answer;
      let comment = this.t.at(index).value.comment;
      let document = this.uchecklistfiles[x.id];

      if(answer=='' || answer===null){
        formerrors = true;
      }
      if(comment=='' || comment===null){
        formerrors = true;
      }
      if(x.file_upload_required){
        if(!this.uchecklistfiles[x.id] || this.uchecklistfiles[x.id] ==''){
          this.checklistFileError[x.id] = 'Please upload file';
          formerrors = true;
        }
      }

      //expchecklist.push({answer:answer,comment:comment,question_id:x.id,question:x.name,document:document});
    });
	/*
	if(this.ceritifedByOtherCertificationBodyFormStatus)
	{
    //this.selStandardIds.forEach((x,index)=>{
		this.standardList.forEach((x,index)=>{
			if(this.selStandardIds.includes(x.id))
			{			
				let validity_date = this.ocb.at(index).value.validity_date;
				let certification_body = this.ocb.at(index).value.certification_body;
				let document = this.certifiedbyothercbfiles[x.id];
        //console.log(validity_date+'=='+certification_body+'=='+document);
        
        this.ocb.at(index).get("validity_date").setValidators([]);
        this.ocb.at(index).get("validity_date").updateValueAndValidity();
        this.ocb.at(index).get("validity_date").markAsTouched();

        this.ocb.at(index).get("certification_body").setValidators([]);
        this.ocb.at(index).get("certification_body").updateValueAndValidity();
        this.ocb.at(index).get("certification_body").markAsTouched();
        this.certifiedbyothercbFileError[x.id] = '';

        if((validity_date!='' && validity_date!==null) || (certification_body!='' && certification_body!==null) || (this.certifiedbyothercbfiles[x.id] && this.certifiedbyothercbfiles[x.id] !=''  && this.certifiedbyothercbfiles[x.id] !==undefined))
				{
          this.ocb.at(index).get("validity_date").setValidators([Validators.required]);
          this.ocb.at(index).get("validity_date").updateValueAndValidity();
          this.ocb.at(index).get("validity_date").markAsTouched();

          this.ocb.at(index).get("certification_body").setValidators([Validators.required]);
          this.ocb.at(index).get("certification_body").updateValueAndValidity();
          this.ocb.at(index).get("certification_body").markAsTouched();

          if(!this.certifiedbyothercbfiles[x.id] || this.certifiedbyothercbfiles[x.id] =='' || this.certifiedbyothercbfiles[x.id] === undefined)
					{
						this.certifiedbyothercbFileError[x.id] = 'Please upload file';
						formerrors = true;
          }
          if(validity_date=='' || validity_date===null){
            formerrors = true;
          }
          if(certification_body=='' || certification_body===null){
            formerrors = true;
          }
        }
        
			}	
		});
	}	
	return false;
  */
  /*
				if(validity_date!='' || validity_date!==null || certification_body!='' || certification_body!==null || this.certifiedbyothercbfiles[x.id] || this.certifiedbyothercbfiles[x.id] !='')
				{
					if(validity_date=='' || validity_date===null)
					{
						formerrors = true;
					}
				
					if(certification_body=='' || certification_body===null)
					{
						formerrors = true;
					}	
			  
					if(!this.certifiedbyothercbfiles[x.id] || this.certifiedbyothercbfiles[x.id] =='')
					{
						this.certifiedbyothercbFileError[x.id] = 'Please upload file';
						formerrors = true;
					}   			
        }
        */
    if(company_name =='' || company_address=='' || zipcode=='' || country_id=='' || country_id==null || state_id==null || state_id==''
      || city =='' || salutation=='' || first_name=='' || last_name=='' || job_title=='' || company_telephone==''
      || company_email=='' || sel_brand_ch=='')
    {
      formerrors= true;
    }
    if((this.brand_file =='' || this.brand_file==null) && this.f.sel_brand_ch.value==1 ){
      this.brandFileError ='Please upload brand file';
      formerrors=true;
    }
   
    /*if(company_name =='' || company_address=='' || zipcode=='' || country_id=='' || state_id==''
      || city =='' || company_file=='' || salutation=='' || first_name=='' || last_name=='' || job_title=='' || company_telephone==''
      || company_email=='')
    {
      formerrors= true;
    }*/
	
	// --------  Standard Addition Code Start Here ---------	
	if(this.standardsLength>0)
	{
		if(this.standard_addition_id!==null && this.standard_addition_id>0)
		{
			let selectedStdCount=0;
			this.selStandardIds.forEach((val)=>{				
				if(this.standardAdditionList.includes(val))
				{
					selectedStdCount = selectedStdCount+1;	
				}
			});			
			
			if(selectedStdCount==0 || (this.standardAdditionList.length>0 && selectedStdCount>0 && this.standardAdditionList.length!=selectedStdCount))
			{
				this.appstandardErrors = 'Please select standards';
				this.error = {summary:this.errorSummary.errorSummaryText};
				return false;				
			}					
		}
	}		
	// --------  Standard Addition Code End Here ---------
	
    if (formerrors || this.unitEntries.length<=0 || this.productListDetails.length<=0 || this.standardsLength<=0 || this.companyFileError!='') {
      if(this.unitEntries.length <=0){
        this.unitListError = 'Please add units for certification';
      }
      if(this.productListDetails.length <=0){
        this.productstandard_error = 'Please add products';
      }
      if(this.standardsLength <=0){
        this.appstandardErrors = 'Please select standards';
      }
    
      this.error = {summary:this.errorSummary.errorSummaryText+this.appstandardErrors+this.productstandard_error+this.unitListError};
      
      return false;
    }


    //let standards = standardvals;//this.standardsChkDb.concat(this.enquiryForm.get('standardsChk').value);
    
    this.submitted = 1;
    this.submittedError = 0;

    //let productsent = 
    
    let productdatas = [];

    this.productEntries.forEach((val)=>{
      let productStandardList = [];
      val.productStandardList.forEach((listval)=>{
        productStandardList.push({standard_id:listval.standard_id,standard_name:listval.standard_name,label_grade:listval.label_grade,label_grade_name:listval.label_grade_name});
      });
      let productMaterialList=[];
      val.productMaterialList.forEach((listval)=>{
        productMaterialList.push({material_id:listval.material_id,material_name:listval.material_name,material_percentage:listval.material_percentage,material_type_id:listval.material_type_id,material_type_name:listval.material_type_name});
      });
      
      productdatas.push({addition_type:val.addition_type,product_id:val.id,name:val.name,wastage:val.wastage,product_type:val.product_type_id,productStandardList:productStandardList,productMaterialList});
    });
    /*
    this.productListDetails.forEach((val)=>{
      
      let productStandardList = [];
      let productMaterialList=[];
      val.productMaterialList.forEach((listval)=>{
        productMaterialList.push({material_id:listval.material_id,material_name:listval.material_name,material_percentage:listval.material_percentage,material_type_id:listval.material_type_id,material_type_name:listval.material_type_name});
      });
      productdatas.push({pdt_index:val.pdt_index,product_id:val.id,name:val.name,wastage:val.wastage,product_type:val.product_type_id,standard_id:val.standard_id,label_grade:val.label_grade,productMaterialList});
    });
    */
    

   
    let unitDataEntries = [];
    
    this.unitproductErrors ='';
    this.unitprocessErrors ='';
    this.unitstandardErrors ='';
    let bsectorerrorList:any = [];
    this.unitEntries.forEach((val,unitindexcal)=>{
      //processDataEntries.push({id:val.id})
      if(val.deleted){

        unitDataEntries.push({deleted:1});
        
      }else{
          
        let processDataEntries = [];
        let standardDataEntries = [];
        let bsectorList = [];
        let existsprocessDataEntries = [];

        val.sel_process.forEach((val)=>{
          processDataEntries.push(val.id); 
        });
        if(val.sel_processexists){
          val.sel_processexists.forEach((val)=>{
            existsprocessDataEntries.push(val.id);
          });
        }
      
        val.business_sector_id.forEach((val)=>{
          bsectorList.push(val);
        });
        
        val.sel_standard.forEach((val,alreadycertindex)=>{
          let upfilesArr= [];
          let upfiles = val.uploadedFiles;
          let upfilesdetails = val.uploadedFileNames;
          for (let i = 0; i < upfiles.length; i++) {
            upfilesArr.push({name:upfiles[i].name,added:upfilesdetails[i].added,deleted:upfilesdetails[i].deleted,type:upfilesdetails[i].type,fileadded:upfilesdetails[i].fileadded});
            /*
            let adddata = 0;
            let upindex:any = '';
            if(upfilesdetails[i].added ==0 && upfilesdetails[i].deleted==0){
              adddata = 1;
              upindex = 0;
            }else if(upfilesdetails[i].added ==1 && upfilesdetails[i].deleted==0  && upfilesdetails[i].fileadded==1){
              adddata = 1;
              let upFindVal = this.upFilesTrack[unitindexcal].find(xf=>xf.fileadded==1 && xf.deleted==0 && xf.added==1);
              console.log(upFindVal);
              if(upFindVal){
                upindex = upFindVal.curIndex
              }
              
            }
            if(adddata){
              upfilesArr.push({upindex:upindex,name:upfiles[i].name,added:upfilesdetails[i].added,deleted:upfilesdetails[i].deleted,type:upfilesdetails[i].type,fileadded:upfilesdetails[i].fileadded});
            }
            */
            
          }
           
          let license_number = val.license_number;
          let expiry_date = val.expiry_date;
          standardDataEntries.push({standard:val.id,files:upfilesArr,license_number:license_number,expiry_date:expiry_date});
        });
        if(val.unitProductList.length<=0){
          this.unitproductErrors = 'true';
        }
        if(processDataEntries.length<=0){
          this.unitprocessErrors = 'true';
        }
        if(val.unit_type !=1){
          if(val['selUnitStandardList'].length<=0){
            this.unitstandardErrors = 'true';
          }
        
        }



        let curStdList:any = [];
        if(val.unit_type!=1){
          curStdList = [...val['selUnitStandardList']].map(String);
        }else{
          this.selStandardList.forEach(cs=>{
            curStdList.push(cs.id);
          });
        }
         
        //return false;
        let notExistName:any = [];
        if(curStdList.length > 0){
          curStdList.forEach(selstdID=>{
            let bsectorexistsforStd:any = 0;
            if(this.standardwiseBSector[selstdID] && this.standardwiseBSector[selstdID].length>0){
              if(bsectorList && bsectorList.length>0){
                bsectorList = bsectorList.map(String);
                bsectorList.forEach(bid=>{
                  if(this.standardwiseBSector[selstdID].includes(bid)){
                    bsectorexistsforStd = 1;
                  }
                })
              }
              
              
            }
            if(!bsectorexistsforStd){
              //selstdID
              notExistName.push(this.standardList.find(xx=>xx.id==selstdID).code);
            }
          })
        }
        
        if(notExistName.length>0){
          bsectorerrorList.push("Please add business sector for: " + notExistName.join(', ')+' in '+val.unit_name);
        }
      

        let expobject = {
          "unit_id" : val.unit_id,
          "unit_type":val.unit_type,
          "name": val.unit_name,
          "addition_type":val.addition_type,
          "unit_exists" : val.unit_exists,
          "business_sector_exists" :val.business_sector_exists,
          "address":val.unit_address,
          "zipcode":val.unit_zipcode,
          "country_id":val.unit_country_id,
          "state_id":val.unit_state_id,
          "products":val.unitProductList,
          
          
          "city":val.unit_city,
          "no_of_employees":val.no_of_employees,
          "business_sector_id":bsectorList,

          "certified_standard":standardDataEntries,
          "processes":processDataEntries,
          "existsprocesses":existsprocessDataEntries,
          "standards":val['selUnitStandardList'],
          //"certFile" : this.uploadedFiles
        }
        unitDataEntries.push(expobject);
      }
    });

    if( bsectorerrorList.length>0 || this.unitproductErrors ||  this.unitprocessErrors || this.unitstandardErrors )
    {
      let listerrarr=[];
      if(this.unitproductErrors){
        listerrarr.push('product');
      }
      if(this.unitprocessErrors){
        listerrarr.push('process');
      }
      if(this.unitstandardErrors){
        listerrarr.push('standard');
      }
      
      let errorstringAll:String = '';
      if(listerrarr.length>0){
        errorstringAll = "Please add "+listerrarr.join("/")+" for all the units<br>";
      }
      this.error = {summary:this.errorSummary.getErrorSummary(errorstringAll+""+bsectorerrorList.join('<br>'),'','')};
      return false;
    }
    //return false;
    
    //sel_process

    //const productsWastageArray = <FormArray>this.enquiryForm.get('productsWastage');
    //productsWastageArray.push(this.productEntries);

    let expchecklist:any=[];
    this.appchecklist.forEach((x,index)=>{
		let answer = this.t.at(index).value.answer;
		let comment = this.t.at(index).value.comment;
		let documentdata = this.uchecklistfiles[x.id]?this.uchecklistfiles[x.id]:'';
		expchecklist.push({answer:answer,comment:comment,question_id:x.id,question:x.name,document:documentdata});
    });
	
	let certifiedothercblist:any=[];
	if(this.ceritifedByOtherCertificationBodyFormStatus)
	{
		this.standardList.forEach((x,index)=>{
			if(this.selStandardIds.includes(x.id))
			{
        let validity_date:any = '';
        if(this.ocb.at(index).value.validity_date){
          validity_date = this.errorSummary.displayDateFormat(this.ocb.at(index).value.validity_date);
        }        
				let certification_body = this.ocb.at(index).value.certification_body;
				let documentdata = this.certifiedbyothercbfiles[x.id]?this.certifiedbyothercbfiles[x.id]:'';
				certifiedothercblist.push({validity_date:validity_date,certification_body:certification_body,standard_id:x.id,document:documentdata});
			}
		});	
	}	

    let formvalue = this.enquiryForm.value;
    formvalue.products = [];
    formvalue.units = [];
    formvalue.products = productdatas;
    formvalue.units = unitDataEntries;
    formvalue.standards = standards;
    formvalue.address = formvalue.company_address;
    formvalue.actiontype = actiontype;
    formvalue.sel_brand_ch = formvalue.sel_brand_ch;
    formvalue.sel_brand = (formvalue.sel_brand_ch==1)?formvalue.sel_brand:'';
    formvalue.telephone = formvalue.company_telephone;
    formvalue.email_address = formvalue.company_email;
    formvalue.id=this.id;    
    formvalue.app_id = this.app_id;
    formvalue.standard_addition_id = this.standard_addition_id;
    formvalue.app_checklist = expchecklist;
	if(this.ceritifedByOtherCertificationBodyFormStatus)
	{
		formvalue.app_certifiedothercblist = certifiedothercblist;
	}

    this.formData.append('formvalues',JSON.stringify(formvalue));
    
    
    //return false;
    
    if (formvalue.units.length>0 && formvalue.products.length>0 && formvalue.standards.length>0) {
      
      /*if(this.standardsLength<=0){
        return false;
      }
      */
      this.loading['button'] = true;

      this.enquiry.updateApplication(this.formData)
      .pipe(first())
      .subscribe(res => {

          if(res.status){
            //this.enquiryForm.reset();
            this.success = {summary:res.message};
			      this.buttonDisable = true;           
            setTimeout(() => {
              this.router.navigateByUrl('/application/apps/view?id='+this.id); 
            }, this.errorSummary.redirectTime);           
          }else if(res.status == 0){
            //this.submittedError =1;
            this.error = {summary:this.errorSummary.getErrorSummary(res.message,this,this.enquiryForm)};
            //this.error = res.msg;
          }else{
            //this.submittedError =1;
            this.error = {summary:res};
          }
          this.loading['button'] = false;
         
      },
      error => {
          this.error = {summary:error};
          this.loading['button'] = false;
      });
       
    } else {
       
      this.error = {summary:this.errorSummary.errorSummaryText};
      this.validateAllFormFields(this.enquiryForm); 
      
    }
  }


  answerArr:any = [];
  appchecklist:any=[];
  checklistFileError:any=[];
  guidanceIncludeList:Array<any> = [];
  uchecklistfiles:any=[];
  certifiedbyothercbfiles:any=[];
  certifiedbyothercbFileError:any=[];

  get appchecklistctrls(): FormArray { 
    return this.enquiryForm.get('appchecklistctrls') as FormArray; 
  }
  get t(): FormArray { return this.enquiryForm.controls.appchecklistfield as FormArray; }
  
  get ocb(): FormArray { return this.enquiryForm.controls.certifiedbyothercertificationbody as FormArray; }  

  toggleGuidance(checklistid){
    let index = this.guidanceIncludeList.indexOf(checklistid);
    if (index > -1) {
      this.guidanceIncludeList.splice(index, 1);
    }else{
      this.guidanceIncludeList.push(checklistid);
    }
  }
  checklistfileChange(element,qid) {
    let files = element.target.files;
    this.checklistFileError[qid] ='';
    let fileextension = files[0].name.split('.').pop();
    if(this.errorSummary.checkValidDocs(fileextension))
    {

      this.formData.append("checklist_file["+qid+"]", files[0], files[0].name);
      this.uchecklistfiles[qid] = files[0].name;
      
    }else{
      this.checklistFileError[qid] ='Please upload valid file';
    }
    element.target.value = '';
   
  }
  removechecklistFile(qid){
    this.uchecklistfiles[qid] = '';
    this.formData.delete('checklist_file['+qid+']');
  }
  
  certifiedbyothercbfileChange(element,qid) {
    let files = element.target.files;
    this.certifiedbyothercbFileError[qid] ='';
    let fileextension = files[0].name.split('.').pop();
    if(this.errorSummary.checkValidDocs(fileextension))
    {

      this.formData.append("certifiedbyothercb_file["+qid+"]", files[0], files[0].name);
      this.certifiedbyothercbfiles[qid] = files[0].name;
      this.fnValidateCertificationBody();
    }else{
      this.certifiedbyothercbFileError[qid] ='Please upload valid file';
    }
    element.target.value = '';
    
  }
  removecertifiedbyothercbFile(qid){
    this.certifiedbyothercbfiles[qid] = '';
    this.formData.delete('certifiedbyothercb_file['+qid+']');
    this.fnValidateCertificationBody();
  }
  
  unitproductremainingstatus=true;
  selProductStandardList:Array<any> = [];
  logsuccess:any;
  popunitproductlist:any = [];
  addUnitProductPop(content)
  {	

    if(this.productListDetails && this.productListDetails.length>0){
      this.productListDetails.forEach(pdtdata=>{
        this.popunitproductlist['input_weight'+pdtdata.pdt_index] = false;
      })
    }
    if(this.currentunittype==1)
    {		
      
      this.selProductStandardList=[...this.selStandardIds];
      //this.selProductStandardList=this.selUnitStandardList;
    }else{
      this.selProductStandardList=[...this.selUnitStandardList];
    }
	
    this.unitproductremainingstatus=true;
    if(this.filterProduct().length==this.unitProductList.length)
    {
      this.unitproductremainingstatus=false;
    }
    this.selectedProductIds = [];
    this.logsuccess = false;
    this.modalss = this.modalService.open(content, {size:'xl',ariaLabelledBy: 'modal-basic-title',centered: true});
	 
	//this.inputloading['stockbutton'] = false;
	//this.inputloading['inputdatabutton'] = false;
	//this.stocksuccess = {};
	//this.stockerror = {};

	//this.remainingWeightError = '';
	//this.remainingWeightSuccess = '';	
	
	//this.remainingcertifiedweight = 0;
	//this.viewinputmaterialdata = data;
		
	
	//this.inputmaterialweightlist['wastage_percentage']= data.wastage_percentage;
	
	//this.getStandardwisematerial(data.id);  
	
	//this.inputdata = '';
	//this.editProductStatus=0; 
	
    
  }
  
  getStandardName(stdId:number){
    let std= this.standardList.find(s => s.id ==  stdId);
    return std.name;
  }
  
 
  productpopupsuccess:any;
  productpopuperror:any;
  addUnitProductFromPop(){
    		
	this.productpopuperror='';
    if(this.selectedProductIds.length<=0){
      this.productpopuperror = {summary:"Please select the product"};
      return false;
    }  
   
    this.selectedProductIds.forEach(pdt=>{
      let selunitproduct = this.productListDetails.find(s => s.pdt_index ==  pdt); 
      let entry= this.unitProductList.find(s => s.pdt_index ==  pdt);
      if(entry === undefined){
        //this.unitProductList.push(selunitproduct);
        this.unitProductList.push({...selunitproduct,addition_type:1});
      }
    });
	
	this.modalss.close();   
  }
}
