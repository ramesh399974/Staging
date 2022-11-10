import { Component, OnInit,Input } from '@angular/core';
import { FormArray, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';
import { ActivatedRoute ,Params, Router } from '@angular/router';
import { AuthenticationService } from '@app/services/authentication.service';
import { AuditFileUploadsService } from '@app/services/audit/audit-file-uploads.service';
import { first } from 'rxjs/operators';
import { NgbdSortableHeader, SortEvent,PaginationList,commontxt } from '@app/helpers/sortable.directive';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import {saveAs} from 'file-saver';

@Component({
  selector: 'app-audit-file-uploads',
  templateUrl: './audit-file-uploads.component.html',
  styleUrls: ['./audit-file-uploads.component.scss']
})
export class AuditFileUploadsComponent implements OnInit {
 
  @Input() cond_viewonly: any;
  title = 'Audit File Uploads'; 
  remarkForm: FormGroup;
  dataloaded = false;
  isItApplicable=true;
  buttonDisable = false;
  loading:any=[];
  audit_id: number;
  unit_id: number;
  app_id: number;
  standard_id: number;
  audit_plan_unit_id: number;
  success:any;
  error:any;
  auditFileForm: FormGroup;
  reportList: any=[];
  formData:FormData = new FormData();
  modalss:any;
  loadingFile: boolean;
  constructor(private activatedRoute:ActivatedRoute, private modalService: NgbModal,private router: Router,private fb:FormBuilder, public errorSummary: ErrorSummaryService, private authservice:AuthenticationService,private service : AuditFileUploadsService){}

  ngOnInit() {
    this.audit_id = this.activatedRoute.snapshot.queryParams.audit_id;
    this.unit_id = this.activatedRoute.snapshot.queryParams.unit_id;
    this.app_id = this.activatedRoute.snapshot.queryParams.app_id;
    this.standard_id=this.activatedRoute.snapshot.queryParams.standard_id;
    this.audit_plan_unit_id =this.activatedRoute.snapshot.queryParams.audit_plan_unit_id;

    this.remarkForm = this.fb.group({	
      remark:['',[Validators.required, this.errorSummary.noWhitespaceValidator,Validators.maxLength(255)]]
    });

    this.auditFileForm = this.fb.group({
      reports:new FormArray([]),
    })

    this.service.getReports({audit_id:this.audit_id,unit_id:this.unit_id}).pipe(first())
    .subscribe(res => {  
      this.dataloaded = true;
      if(res!==null)
      {  
        this.reportList = res.reportlist; 
        this.ureportfiles = res.auditreportslist;
        this.reportList.forEach((x,index)=>{    
          this.t.push(this.fb.group({
            audit_files: ['', Validators.required],
          }));
        })
      }
    },
    error => {
        this.error = error;
        this.loading['button'] = false;
    });

    this.service.getRemarkData({audit_id:this.audit_id,audit_plan_unit_id: this.audit_plan_unit_id,standard_id:this.standard_id,unit_id:this.unit_id,type:'audit_file_uploads'}).pipe(first())
    .subscribe(res => {  
      this.dataloaded = true;
      if(res!==null)
      {  
        this.isApplicable = res.status;
        if(res.status==1)
        {
          this.isItApplicable=true; 
        }else{
          this.isItApplicable=false;
        }	 
        
        if(res.comments)
        {
          this.remarkForm.patchValue({
            'remark':res.comments
          });
        }
      }
    },
    error => {
        this.error = error;
        this.loading['button'] = false;
    });
  }

  get rf() { return this.remarkForm.controls; }
  get t(): FormArray { return this.auditFileForm.controls.reports as FormArray; }

  isApplicable:number;
  isItApp(arg)
  {
    this.isApplicable = arg;
	  if(arg==1)
	  {
      this.isItApplicable=true; 
	  }else{
		  this.isItApplicable=false;
	  }	  
  }
  ureportfiles:any=[];
  reportFileError:any=[];
  audit_filesChange(element,qid) {
    let files = element.target.files;
    this.reportFileError[qid] ='';
    let replength = this.ureportfiles.length;
    let qlenth = this.ureportfiles.filter(x=>x.id==qid).length;
    let fileextension = files[0].name.split('.').pop();
    if(this.errorSummary.checkValidDocs(fileextension))
    {
      this.formData.append("audit_file["+qid+"]["+qlenth+"]", files[0], files[0].name);
      this.ureportfiles.push({name:files[0].name,added:1,deleted:0,fileadded:0,id:qid,index:replength});
      
    }else{
      this.reportFileError[qid] ='Please upload valid file';
    }
    element.target.value = '';
  }
  removeaudit_file(qid,index){

    let filenames =  this.ureportfiles.map(x => {
      if(x.deleted==0 && x.id==qid && x.index==index ){
        x.deleted=1;
      }
      return x;
    });
    this.ureportfiles = filenames;
  }
  filterAuditFile(id){
    return this.ureportfiles.filter(x=>x.deleted==0 && x.id==id);
  }
  addRemark()
  {
    this.rf.remark.markAsTouched();

    if(this.remarkForm.valid)
    {
      this.buttonDisable = true;
      this.loading['button'] = true;

      let remark = this.remarkForm.get('remark').value;

      let expobject:any={unit_id:this.unit_id,audit_plan_unit_id: this.audit_plan_unit_id,standard_id:this.standard_id,audit_id:this.audit_id,comments:remark,is_applicable:this.isApplicable,type:'audit_file_uploads'}

      this.service.addRemark(expobject)
      .pipe(first())
      .subscribe(res => {
        if(res.status)
        {
          this.success = {summary:res.message};
          this.buttonDisable = false;
          this.loading['button'] = false;
        }
      },
      error => {
          this.error = {summary:error};
          this.loading['button'] = false;
      });
    }
  }
  openmodal(content,arg='') {
    this.modalss = this.modalService.open(content, {ariaLabelledBy: 'modal-basic-title',centered: true});
  } 
  editStatus=0;
  remarkFormreset()
  {
    this.editStatus=0;
    this.remarkForm.reset();
    
    this.remarkForm.patchValue({     
      remark:''
    });
  }
  downloadAuditReports(val,filename){
    this.service.download({filename:filename,id:val})
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

  onSubmit()
  {
      let formerrors = false;

      let formvalues =[]
      this.reportList.forEach((x,index)=>{

        let qreportslen = this.ureportfiles.filter(l =>l.id ==x.id && l.deleted!=1).length;
        if(qreportslen<=0){
          this.reportFileError[x.id] = 'Please upload file';
          formerrors = true;
        }

        let id = x.id;
        let report_name = x.report_name;

        let qreports = this.ureportfiles.filter(r => r.id==x.id);
        formvalues.push({id:id,report_name:report_name,qreports:qreports}) 
      });

      let expobject:any={app_id:this.app_id,unit_id:this.unit_id,audit_id:this.audit_id,audit_plan_unit_id:this.audit_plan_unit_id,reports:formvalues,type:'audit_file_uploads'}
      this.formData.append('formvalues',JSON.stringify(expobject));
      if(!formerrors)
      {
        this.loading['button'] = true;
        // this.buttonDisable = true;
        this.service.addReports(this.formData)
        .pipe(first())
        .subscribe(res => {
          if(res.status)
          {
            this.success = {summary:res.message};
            // this.buttonDisable = false;
            this.loading['button'] = false;
          }
        },
        error => {
            this.error = {summary:error};
            this.loading['button'] = false;
        });
    }
  }
}
