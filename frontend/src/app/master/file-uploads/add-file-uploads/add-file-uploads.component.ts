import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';
import { FileUploadsService } from '@app/services/master/file-uploads/file-uploads.service';
import { ProcessService } from '@app/services/master/process/process.service';
import { Standard } from '@app/services/standard';
import { StandardService } from '@app/services/standard.service';
import { first } from 'rxjs/operators';

@Component({
  selector: 'app-add-file-uploads',
  templateUrl: './add-file-uploads.component.html',
  styleUrls: ['./add-file-uploads.component.scss']
})
export class AddFileUploadsComponent implements OnInit {
  title = 'Add File Upload';
  btnLabel = 'Save';
  form : FormGroup;
  standardList:Standard[];
  loading = false;
  buttonDisable = false;
  error:any;
  submittedError = false;
  success:any;
  nameErrors = '';
  processList: any=[];

  constructor(private router: Router,private standardservice: StandardService,private fileUploadsService: FileUploadsService,private processService:ProcessService,private fb:FormBuilder,private errorSummary: ErrorSummaryService) { }

  ngOnInit() {

    this.standardservice.getStandard().subscribe(res => {
      this.standardList = res['standards'];
      });

      this.processService.getProcessList().pipe(first()).subscribe(res => {
        this.processList = res['processes'];      
     });	

    this.form = this.fb.group({
      name:['',[Validators.required, this.errorSummary.noWhitespaceValidator, Validators.maxLength(255),Validators.pattern("^[a-zA-Z0-9 \'\-+%/&,().-]+$")]],      
    standard_id:['',[Validators.required]],  
    // file_upload_consent:['',[Validators.required]],
    process_id :['',[Validators.required]],   
    });
  }
  getSelectedValue(type,val)
  {
     if(type=='standard_id'){
      return this.standardList.find(x=> x.id==val).name;
    }else if(type=='process_id'){
      return this.processList.find(x=> x.id==val).name;
    }
  }
  get f() { return this.form.controls; }
  onSubmit(){
  
    if (this.form.valid) {

      this.loading = true;
      
      this.fileUploadsService.addData(this.form.value)
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
