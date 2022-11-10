import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';
import { FileUploadsService } from '@app/services/master/file-uploads/file-uploads.service';
import { ProcessService } from '@app/services/master/process/process.service';
import { Standard } from '@app/services/standard';
import { StandardService } from '@app/services/standard.service';
import { first } from 'rxjs/operators';

@Component({
  selector: 'app-edit-file-uploads',
  templateUrl: '../add-file-uploads/add-file-uploads.component.html',
  styleUrls: ['./edit-file-uploads.component.scss']
})
export class EditFileUploadsComponent implements OnInit {

  title = 'Edit File Upload';
  btnLabel = 'Update';
  form : FormGroup;
  loading = false;
  buttonDisable = false;
  error:any;
  id:number;
  success:any;
  submittedError = false;

  //audittype:Audittype;
  standardList:Standard[];
  processList: any=[];
  standard_Ids: any=[];
  process_Ids: any=[];
  constructor(private activatedRoute:ActivatedRoute,private standardservice: StandardService,private fileUploadsService: FileUploadsService,private processService:ProcessService,private router: Router,private fb:FormBuilder,private errorSummary: ErrorSummaryService) { }

  ngOnInit() {
	this.id = this.activatedRoute.snapshot.queryParams.id;
  
  this.standardservice.getStandard().subscribe(res => {
    this.standardList = res['standards'];
    });
    this.processService.getProcessList().pipe(first()).subscribe(res => {
      this.processList = res['processes'];      
   });	
    this.form = this.fb.group({
      id:[''],
      name:['',[Validators.required, this.errorSummary.noWhitespaceValidator, Validators.maxLength(255),Validators.pattern("^[a-zA-Z0-9 \'\-+%/&,().-]+$")]],
      standard_id:['',[Validators.required]],  
      // file_upload_consent:['',[Validators.required]],
      process_id :['',[Validators.required]],    
    });

    this.fileUploadsService.getFileUploadDetails(this.id).pipe(first())
    .subscribe(res => {
      let fdata = res.data;

      if(fdata.standard_id.length>0){
        fdata.standard_id.forEach(std=>{
          this.standard_Ids.push(""+std+"")
        })
      }
      if(fdata.process_id.length>0){
        fdata.process_id.forEach(prs=>{
          this.process_Ids.push(""+prs+"")
        })
      }
	  
      this.form.patchValue({
        id:fdata.id,
        name: fdata.name,
        standard_id : this.standard_Ids,
        process_id : this.process_Ids 
      });
    },
    error => {
        this.error = error;
        this.loading = false;
    });
  }
  
  get f() { return this.form.controls; }
  getSelectedValue(type,val)
  {
     if(type=='standard_id'){
      return this.standardList.find(x=> x.id==val).name;
    } if(type=='process_id'){
      return this.processList.find(x=> x.id==val).name;
    }
  }
  onSubmit(){
  
    if (this.form.valid) {

      this.loading = true;
      
      this.fileUploadsService.updateData(this.form.value)
      .pipe(
        first()        
      )
      .subscribe(res => {
        //console.log(res);
          if(res.status){			  
            this.success = {summary:res.message};
			this.buttonDisable = true;
			setTimeout(()=>this.router.navigate(['/master/list-file-uploads/index']),this.errorSummary.redirectTime);
          }else if(res.status == 0){
            this.error = {summary:this.errorSummary.getErrorSummary(res.message,this,this.form)};			      
          }else{			      
            this.error = {summary:res};
          }
          this.loading = false;
         
      },
      error => {
          this.error = {summary:error};
          this.loading = false;
      });      
    } else {
      this.error = {summary:this.errorSummary.errorSummaryText};
      this.errorSummary.validateAllFormFields(this.form); 
      
    }
  }
}
