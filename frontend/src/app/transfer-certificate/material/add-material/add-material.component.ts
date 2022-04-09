import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators, FormControl,FormArray } from '@angular/forms';
import { MaterialService } from '@app/services/transfer-certificate/material/material.service';
import { ProductService } from '@app/services/master/product/product.service';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';
import { Router } from '@angular/router';
import { first } from 'rxjs/operators';

import { StandardService } from '@app/services/standard.service';


@Component({
  selector: 'app-add-material',
  templateUrl: './add-material.component.html',
  styleUrls: ['./add-material.component.scss']
})
export class AddMaterialComponent implements OnInit {

  title = 'Add Material';
  btnLabel = 'Save';
  
  form : FormGroup;
  loading = false;
  buttonDisable = false;
  error:any;
  submittedError = false;
  success:any;

  nameErrors = '';
  codeErrors = '';
  
  formData:FormData = new FormData();
  standardList: any;
  
  
  constructor(private standards: StandardService,private router: Router,private fb:FormBuilder,private productService: ProductService,private materialService:MaterialService,private errorSummary: ErrorSummaryService) { }

  ngOnInit() {
    this.standards.getStandard().subscribe(res =>{
      this.standardList = res['standards'];
    })

	this.form = this.fb.group({
    name:['',[Validators.required, this.errorSummary.noWhitespaceValidator, Validators.maxLength(255), Validators.pattern("^[a-zA-Z0-9 \'\-+%/&,().-]+$")]],
	  code:['',[Validators.required, this.errorSummary.noWhitespaceValidator, Validators.maxLength(50),Validators.pattern("^[a-zA-Z0-9 \'\-+%/&,().-]+$")]],
    standard_id:['',[Validators.required]], 
    });
  }
  
 
  
  getSelectedValue(type,val)
  {
     if(type='standard_id'){
      return this.standardList.find(x=> x.id==val).name;
    }
  }
  get f() { return this.form.controls; }
    
  onSubmit(){
    let formerror =false;
   
    this.f.name.markAsTouched();
    this.f.code.markAsTouched();
    this.f.standard_id.markAsTouched();
    
    let name = this.form.get('name').value;
    let code = this.form.get('code').value;
    let standard_id = this.form.get('standard_id').value;

    if( name =='' || name==null || code =='' || code==null ){
      formerror=true;
    }
      
    if(standard_id.length==0){
      formerror=true;
    }
    
    if (!formerror) {
      
      this.loading = true;
	  
	  this.materialService.addData(this.form.value)
      .pipe(
        first()        
      ).subscribe(res => {
          if(res.status){
            this.success = {summary:res.message};
			this.buttonDisable = true;
            setTimeout(()=>this.router.navigate(['/transaction-certificate/material/index']),this.errorSummary.redirectTime);            
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
