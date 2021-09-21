import { Component, OnInit } from '@angular/core';

import { ActivatedRoute ,Params, Router } from '@angular/router';
import { ApplicationDetailService } from '@app/services/application/list/application-detail.service';
import { Application } from '@app/models/application/application';
import { UserService } from '@app/services/master/user/user.service';
import { User } from '@app/models/master/user';
import { EnquiryDetailService } from '@app/services/enquiry-detail.service';
import { AuthenticationService } from '@app/services';
import {Observable,Subject} from 'rxjs';
import { first, debounceTime, distinctUntilChanged, map,tap } from 'rxjs/operators';
import {NgbModal, ModalDismissReasons} from '@ng-bootstrap/ng-bootstrap';
import {saveAs} from 'file-saver';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';


import { NgForm, FormGroup, FormBuilder, Validators, FormControl,FormArray } from '@angular/forms';
import { BrandService } from '@app/services/master/brand/brand.service';
//import { AppdetailComponent } from '../../appdetail/appdetail.component';

@Component({
  selector: 'app-view',
  templateUrl: './view.component.html',
  styleUrls: ['./view.component.scss']
})
export class ViewComponent implements OnInit {
  modtitle: string='';
  brandchangeForm: FormGroup;
  brandlist: any;
  brand: any;
  brand_name:any;
  brand_number:any;
  brand_version:any;
  brand_status:any;
  btnEnable: boolean;
  brand_id: any;
  app_approver_brand_id: any;
  brand_ids: any=[];
  loadingFile: boolean;
  brand_file: string;
  formData:FormData = new FormData();
  brandFileError: string;

	
	constructor(private userservice: UserService,private activatedRoute:ActivatedRoute,public fb:FormBuilder, public  brandservice:BrandService,
		private applicationDetail:ApplicationDetailService, private modalService: NgbModal,
		private enquiryDetail:EnquiryDetailService,private router:Router,private authservice:AuthenticationService,public errorSummary: ErrorSummaryService) { 
    }
	
  userdecoded:any;
  bsector_group_id=[];
  id:number;
  error:any;
  success:any;
  loading = false;
  applicationdata:Application;
  panelOpenState = true;
  approvalStatusList = [];//[{id:'1',name:'Accept'},{id:'2',name:'Reject'}];
  userList:User[];
  modalss:any;
  sel_user_error='';
  user_id_error='';
  approver_user_id = '';
  
  model:any = {user_id:'',approver_user_id:'',status:'',comment:'',reject_comment:''};

  userType:number;
  userdetails:any;
  arrEnumStatus:any[];
  revieweruserList:User[];
  approveruserList:User[];
  sectorlist:any=[];
  bsectorgpUpdate = new Subject<any>();

  ngOnInit() {
    
    this.id = this.activatedRoute.snapshot.queryParams.id;
    this.brand_id = this.activatedRoute.snapshot.queryParams.brand_id;
    this.app_approver_brand_id = this.activatedRoute.snapshot.queryParams.brand_approve_id;

    this.brandchangeForm = this.fb.group({
      sel_brand : ['',[Validators.required]],
      brand_ids: ['',[Validators.required]],
      brand_file:['']
    })
    /*
    this.userservice.getAllUser({type:1}).pipe(first())
    .subscribe(res => {
      this.userList = res.users;
    },
    error => {
        this.error = {summary:error};
    });
    */
    this.brandservice.getData().subscribe(res=>{
      this.brandlist = res.data;
    });

    this.bsectorgpUpdate.pipe(
			debounceTime(700),
			distinctUntilChanged())
		.subscribe(value => {
      this.loading  = true;
      this.enquiryDetail.getSectorgpUserList(value)
       .pipe(first())
       .subscribe(res => {
            let unit_id = value.unit_id;
            let bsector = value.business_sector_id;
           // console.log(unit_id+'=='+bsector);
           if(res.status==1){
              let indexv = this.sectorlist.findIndex(x=>x.unit_id == unit_id && x.bsector==bsector);
              if(indexv !== -1){
                this.sectorlist[indexv] = { unit_id:unit_id,bsector:bsector,data: res['data']};
              }else{
                this.sectorlist.push({ unit_id:unit_id,bsector:bsector,data: res['data']});
              }
            }else if(res.status == 0){
              this.error = {summary:res.message};
            }else{
              this.error = {summary:res};
            }
            this.loading = false;
          
       },
       error => {
           this.error = {summary:error};
           this.loading = false;
       });
    });
	
    this.authservice.currentUser.subscribe(x => {
      if(x){
        
         
        let user = this.authservice.getDecodeToken();
        this.userType= user.decodedToken.user_type;
        this.userdetails= user.decodedToken;
        //console.log(this.userdetails);
        
      }else{
        this.userdecoded=null;
      }
    });
    
    let dataobj:any = {id:this.id,brand_id:this.brand_id,actiontype:'view',app_approver_brand_id:this.app_approver_brand_id};
    this.brandservice.getApplicationDetails(dataobj)
    .pipe(
      
      tap(res=>{
        let franchise_id = res.franchise_id;
        this.userservice.getAppApprover({franchise_id:franchise_id}).pipe(first())
        .subscribe(resapprovers => {
          this.approveruserList = resapprovers['data'];
        },
        error => {
            this.error = {summary:error};
        });
        this.userservice.getAppReviewer({franchise_id:franchise_id}).pipe(first())
        .subscribe(resreviewers => {
          this.revieweruserList = resreviewers['data'];
        },
        error => {
            this.error = {summary:error};
        });
      },
      first())
    ).subscribe(res => {
      this.applicationdata = res;
      this.approvalStatusList = res['approvalStatusList'];
      this.arrEnumStatus = res['arrEnumStatus'];
      /*if(this.applicationdata.status == this.arrEnumStatus['submitted']){
        this.panelOpenState = true;
      }
       this.panelOpenState = true; */
       this.brandchangeForm.patchValue({
        brand_ids:this.applicationdata.brandids,
     })
     this.brand_file=res.brand_file;
      if(this.applicationdata.app_status==this.arrEnumStatus['submitted']){
        this.applicationdata.units.forEach((val)=>{
          let bsectorsselgroup = val['bsectorsselgroup'];
          if(bsectorsselgroup && bsectorsselgroup.length>0){
            bsectorsselgroup.forEach((element,key) => {
              this.bsector_group_id['qtd_'+element.unit_id+'_'+element.sector_id]=element.business_sector_group_ids;

              this.getbusinessgpusers(element.business_sector_group_ids,element.unit_id,element.sector_id);
            });
          }
          
        });
      }
      
      

      //bsectorsselgroup
      //console.log(this.applicationdata);
      this.model.approver_user_id = res.approverid ? res.approverid: '';
      this.model.user_id = res.reviewerid ? res.reviewerid: '';
    },
    error => {
        this.error = {summary:error};
        this.loading = false;
    });

   
  } 

  get f() { return this.brandchangeForm.controls;} 
  
  
  status_error = false;
  comment_error =false;
  reject_comment_error =false;
  checkUserSel(user_type='',action=''){
    let formerror =false;
    if(this.brand_file =='' || this.brand_file==null ){
      this.brandFileError ='Please upload brand file';
      formerror= true;
    }
    if(!formerror && user_type=='editbrand'){
      this.brand = this.brandchangeForm.get('sel_brand').value;
      this.modalss.close('editbrand');
    }
    user_type= this.modtitle;
    //console.log(user_type);
    
    if(user_type =='Brand Reject'){
      this.reject_comment_error =false;
      if(this.model.reject_comment ==''){
        this.reject_comment_error =true;
      }else{
        this.modalss.close('brandreject');
      }
    }else if(user_type =='Brand Approve'){
      this.reject_comment_error =false;
      if(this.model.reject_comment ==''){
        this.reject_comment_error =true;
      }else{
        this.modalss.close('brandapprove');
      }
    }
  }
  

  getbusinessgpusers(bsectorgp_id,unit_id,bsector){
    let value:any = {franchise_id:this.applicationdata.franchise_id,bsectorgp_id,unit_id,business_sector_id:bsector};

    this.enquiryDetail.getSectorgpUserList(value)
       .pipe(first())
       .subscribe(res => {
            let unit_id = value.unit_id;
            let bsector = value.business_sector_id;
           // console.log(unit_id+'=='+bsector);
           if(res.status==1){
              let indexv = this.sectorlist.findIndex(x=>x.unit_id == unit_id && x.bsector==bsector);
              if(indexv !== -1){
                this.sectorlist[indexv] = { unit_id:unit_id,bsector:bsector,data: res['data']};
              }else{
                this.sectorlist.push({ unit_id:unit_id,bsector:bsector,data: res['data']});
              }
            }else if(res.status == 0){
              this.error = {summary:res.message};
            }else{
              this.error = {summary:res};
            }
            this.loading = false;
          
       },
       error => {
           this.error = {summary:error};
           this.loading = false;
       });
  }


  downloadCompanyFile(filename){
    this.enquiryDetail.downloadCompanyFile({id:this.id})
    .subscribe(res => {
      
      let fileextension = filename.split('.').pop(); 
      let contenttype = this.errorSummary.getContentType(filename);
      saveAs(new Blob([res],{type:contenttype}),filename);
      this.modalss.close('');
    });
  }

  downloadChecklistFile(fileid,filename){
    this.enquiryDetail.downloadChecklistFile({id:fileid})
    .subscribe(res => {
      
      let fileextension = filename.split('.').pop(); 
      let contenttype = this.errorSummary.getContentType(filename);
      saveAs(new Blob([res],{type:contenttype}),filename);
      this.modalss.close('');
    });
  }

  downloadcertificateFile(fileid,filename){
    this.enquiryDetail.downloadcertificateFile({id:fileid})
    .subscribe(res => {
      
      let fileextension = filename.split('.').pop(); 
      let contenttype = this.errorSummary.getContentType(filename);
      saveAs(new Blob([res],{type:contenttype}),filename);
      this.modalss.close('');
    });
  }

  downloadFile(fileid,filename){
    this.enquiryDetail.downloadFile({id:fileid})
    .subscribe(res => {
      
      let fileextension = filename.split('.').pop(); 
      let contenttype = this.errorSummary.getContentType(filename);
      saveAs(new Blob([res],{type:contenttype}),filename);
      this.modalss.close('');
    });
  }

  removebrandFile(){
    this.brand_file = '';
    this.formData.delete('brand_file');
  }

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

  DownloadFile(val,filename)
  {
    this.loadingFile  = true;
    this.brandservice.downloadFile(val)
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

  openmodal(content) {
    this.modalss = this.modalService.open(content, {ariaLabelledBy: 'modal-basic-title',centered: true});
  }
  editRedirect(id){
    if(this.applicationdata.audit_type == 5){
      this.router.navigateByUrl('/change-scope/unit-addition/add?id='+this.applicationdata.addition_id+'&app='+this.applicationdata.parent_app_id+'&new_app_id='+this.applicationdata.id);
    }else if(this.applicationdata.audit_type == 3){
      this.router.navigateByUrl('/change-scope/process-addition/add?id='+this.applicationdata.addition_id+'&app='+this.applicationdata.parent_app_id+'&new_app_id='+this.applicationdata.id);
    }else if(this.applicationdata.audit_type == 4){
      this.router.navigateByUrl(`/application/edit-request?id=${id}&app_id=${this.applicationdata.parent_app_id}&standard_addition_id=${this.applicationdata.addition_id}`);
    }else{
      this.router.navigateByUrl('/application/edit-request?id='+id);
    }
    
  }
  logForm(val){
    //console.log(JSON.stringify(this.model));
  }
  //onSubmit(f:NgForm,actiontype) {
    appform : any = {};
    bsectrogpData:any = [];
  open(content,type='',f:NgForm) {

    if(type=='brandreject'){
      this.modtitle='Brand Reject';
    }else if(type=='brandapprove'){
      this.modtitle='Brand Approve';
    }

    this.modalss = this.modalService.open(content, {ariaLabelledBy: 'modal-basic-title',centered: true});
    
    this.modalss.result.then((result) => {
      console.log(result);
      this.user_id_error ='';
      this.status_error =false;
      this.comment_error =false;

      
      if(result =='brandreject'){
        
        //this.submitForReview();
        this.rejectBrand(result);
      }else if(result =='brandapprove'){
        
        //this.submitForReview();
        this.rejectBrand(result);
      }else if(result=='editbrand'){
        this.brandchange(result);
      }
      
      

      
      
    }, (reason) => {
      this.user_id_error ='';
      this.status_error =false;
      this.comment_error =false;
      this.model.reject_comment ='';
      this.reject_comment_error =false;
      //this.closeResult = `Dismissed ${this.getDismissReason(reason)}`;
    });
  }

  getSelectedValue(type,val)
  {
    
    if(type='brand_id'){
      return this.brandlist.find(x=> x.id==val).brand_name;
    }
  }

brandchange(result){
  this.brand_ids = this.brandchangeForm.get('brand_ids').value;
  let formvalue = this.brandchangeForm.value;
  formvalue.id=this.id;
  formvalue.actiontype=result;
  formvalue.chbrand_id=this.brand;
  formvalue.brand_id=this.brand_id;
  formvalue.app_approver_brand_id=this.app_approver_brand_id;
  formvalue.brand_ids=this.brand_ids;

  this.formData.append('formvalues',JSON.stringify(formvalue));
  this.brandservice.brandapprove(this.formData)
     .pipe(first())
     .subscribe(res => {
           
         if(res.status==1){
           
           this.btnEnable =true;
            this.success = {summary:res.message};
            this.formData = new FormData();
            
              setTimeout(()=>this.router.navigate(['/brand/list']),this.errorSummary.redirectTime);
            
             //if(this.model.status==4){
              // setTimeout(()=>this.router.navigate(['/brand/list']),this.errorSummary.redirectTime);
            //}
          }else if(res.status == 0){
            this.error = {summary:res.message};
          }else{
            this.error = {summary:res};
          }
          this.loading = false;
        
     },
     error => {
         this.error = {summary:error};
         this.loading = false;
     });
}
  rejectBrand(result){
    this.loading  = true;
    this.brandservice.brandapprove(({id:this.id,reject_comment:this.model.reject_comment,brand_id:this.brand_id,actiontype:result,app_approver_brand_id:this.app_approver_brand_id}))
     .pipe(first())
     .subscribe(res => {
           
         if(res.status==1){
           
           this.btnEnable=false;
            this.success = {summary:res.message};
            //if(this.model.status==4){
              setTimeout(()=>this.router.navigate(['/brand/list']),this.errorSummary.redirectTime);
            //}
          }else if(res.status == 0){
            this.error = {summary:res.message};
          }else{
            this.error = {summary:res};
          }
          this.loading = false;
        
     },
     error => {
         this.error = {summary:error};
         this.loading = false;
     });
  }
  submitForReview(){
    //console.log({app_id:this.id,user_id:this.model.comment,status:this.model.status});
    
    this.loading  = true;
    this.enquiryDetail.submitAppForReview({id:this.id,bsectrogpData:this.bsectrogpData})
     .pipe(first())
     .subscribe(res => {
           
         if(res.status==1){
           
            this.applicationdata.status = res.app_status_name;
            this.applicationdata.app_status = res.app_status;
            this.success = {summary:res.message};
            if(this.model.status==4){
              setTimeout(()=>this.router.navigate(['/application/list']),this.errorSummary.redirectTime);
            }else{
              setTimeout(()=> this.loadApplication(),this.errorSummary.redirectTime);
            }
          }else if(res.status == 0){
            this.error = {summary:res.message};
          }else{
            this.error = {summary:res};
          }
          this.loading = false;
        
     },
     error => {
         this.error = {summary:error};
         this.loading = false;
     });
  }

  apploading:any;
  loadApplication(){
    this.apploading = true;
    this.success = '';
    let dataobj:any = {id:this.id,actiontype:'view'};
    this.applicationDetail.getApplicationDetails(dataobj)
    .pipe(
      first()
    ).subscribe(res => {
      this.applicationdata = res;
      this.apploading = false;
    })
  }


  approveApplication(){
    //console.log({app_id:this.id,user_id:this.model.comment,status:this.model.status});
    
    this.loading  = true;
    this.enquiryDetail.approveApplication({app_id:this.id,comment:this.model.comment,status:this.model.status})
     .pipe(first())
     .subscribe(res => {
           
         if(res.status==1){
            this.applicationdata.status = res.app_status_name;
            this.applicationdata.app_status = res.app_status;
            this.success = {summary:res.message};
            //if(this.model.status==4){
            setTimeout(()=>{ 
              this.router.navigate(['/application/list']);
              this.loading = false;
            },this.errorSummary.redirectTime);
            //}
          }else if(res.status == 0){
            this.error = {summary:res.message};
            this.loading = false;
          }else{
            this.error = {summary:res};
            this.loading = false;
          }
          
        
     },
     error => {
         this.error = {summary:error};
         this.loading = false;
     });
  }
  filtersectorlist(unit_id,bsector){
   // console.log(unit_id+'++'+bsector);
    let sectov = this.sectorlist.find(x=>x.unit_id == unit_id && x.bsector==bsector);
    if(sectov !== undefined){
      return sectov['data'];
    }
    return [];
  }
  getBusinessSectorgpUsers(bsectorgp_id,unit_id,bsector){

    this.bsectorgpUpdate.next({franchise_id:this.applicationdata.franchise_id,bsectorgp_id,unit_id,business_sector_id:bsector});
    //bsectorgpUpdate
   // console.log({franchise_id:this.applicationdata.franchise_id,bsectorgp_id,unit_id,business_sector_id:bsector});
   /*
    this.loading  = true;
    this.enquiryDetail.getSectorgpUserList({franchise_id:this.applicationdata.franchise_id,bsectorgp_id,unit_id,business_sector_id:bsector})
     .pipe(first())
     .subscribe(res => {
           
         if(res.status==1){
            let indexv = this.sectorlist.findIndex(x=>x.unit_id == unit_id && x.bsector==bsector);
            if(indexv !== -1){
              this.sectorlist[indexv] = { unit_id:unit_id,bsector:bsector,data: res['data']};
            }else{
              this.sectorlist.push({ unit_id:unit_id,bsector:bsector,data: res['data']});
            }
           // console.log(this.sectorlist);
           // console.log(unit_id+'=='+bsector);
            //this.applicationdata.status = res.app_status_name;
            //this.applicationdata.status = res.app_status_name;
           // this.applicationdata.app_status = res.app_status;
           // this.applicationdata.hasapprover = res.hasapprover;
           // this.success = {summary:res.message};
            
          }else if(res.status == 0){
            this.error = {summary:res.message};
          }else{
            this.error = {summary:res};
          }
          this.loading = false;
        
     },
     error => {
         this.error = {summary:error};
         this.loading = false;
     });
     */
  }
  assignApprover(action){
    //console.log({app_id:this.id,user_id:this.model.approver_user_id});
    //return false;
    this.loading  = true;
    this.enquiryDetail.assignApprover({actiontype:action,app_id:this.id,user_id:this.model.approver_user_id})
     .pipe(first())
     .subscribe(res => {
           
         if(res.status==1){
            //this.enquirydata.status = res.enquirystatus;
            //this.enquirydata.status_updated_date = res.status_updated_date;
            //this.enquirydata.status_updated_by = res.status_update_by;
            this.applicationdata.status = res.app_status_name;
            this.applicationdata.app_status = res.app_status;
            this.applicationdata.hasapprover = res.hasapprover;
            this.success = {summary:res.message};
            
          }else if(res.status == 0){
            this.error = {summary:res.message};
          }else{
            this.error = {summary:res};
          }
          this.loading = false;
        
     },
     error => {
         this.error = {summary:error};
         this.loading = false;
     });
  }

  addReviewer(action){
    //console.log(action);
      //return false;
    this.loading  = true;
    this.enquiryDetail.assignReviewer({actiontype:action,app_id:this.id,user_id:this.model.user_id})
     .pipe(first())
     .subscribe(res => {
           
         if(res.status){
            //this.enquirydata.status = res.enquirystatus;
            //this.enquirydata.status_updated_date = res.status_updated_date;
            //this.enquirydata.status_updated_by = res.status_update_by;
            this.applicationdata.showApplicationReview = 1;
            this.applicationdata.status = res.app_status_name;
            this.applicationdata.app_status = res.app_status;
            this.applicationdata.hasapprover = res.hasapprover;
            this.success = {summary:res.message};

            
          }else if(res.status == 0){
            this.error = {summary:res.message};
          }else{
            this.error = {summary:res};
          }
          this.loading = false;
        
     },
     error => {
         this.error = {summary:error};
         this.loading = false;
     });
  }
    
  editProduct(content) {	
    this.modalss = this.modalService.open(content, {size:'xl',ariaLabelledBy: 'modal-basic-title',centered: true});
  }

}

