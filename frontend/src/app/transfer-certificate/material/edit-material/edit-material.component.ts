import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators, FormControl,FormArray } from '@angular/forms';
import { ProductService } from '@app/services/master/product/product.service';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { tap,first } from 'rxjs/operators';
import { MaterialService } from '@app/services/transfer-certificate/material/material.service';

import { StandardService } from '@app/services/standard.service';


@Component({
  selector: 'app-edit-material',
  templateUrl: '../add-material/add-material.component.html',
  styleUrls: ['./edit-material.component.scss']
})
export class EditMaterialComponent implements OnInit {

  title = 'Edit Material';
  btnLabel = 'Update';
  id:number;
  
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
  standardids: any=[];
 
  
  constructor(private standards: StandardService,private activatedRoute:ActivatedRoute,private router: Router,private fb:FormBuilder,private materialService:MaterialService,private errorSummary: ErrorSummaryService) { }

  ngOnInit() {
	  
	this.id = this.activatedRoute.snapshot.queryParams.id;
		
	
    
    this.standards.getStandard().subscribe(res =>{
      this.standardList = res['standards'];
    })
	
	this.form = this.fb.group({
	  id:[''],
    name:['',[Validators.required, this.errorSummary.noWhitespaceValidator, Validators.maxLength(255), Validators.pattern("^[a-zA-Z0-9 \'\-+%/&,().-]+$")]],
	  code:['',[Validators.required, this.errorSummary.noWhitespaceValidator, Validators.maxLength(50),Validators.pattern("^[a-zA-Z0-9 \'\-+%/&,().-]+$")]],
    standard_id:['',[Validators.required]], 
    });
	
	this.materialService.getMaterial(this.id).pipe(first(),)
    .subscribe(res => {
      let materialComposition = res.data;

      res.std.forEach(val =>{
        this.standardids.push(""+val+"");
      })
      this.form.patchValue(materialComposition);
      this.form.patchValue({
        standard_id : this.standardids
      })
    },
    error => {
        this.error = error;
        this.loading = false;
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

    if( name =='' || code ==''){
      formerror=true;
    }
   
      if(standard_id.length==0){
        formerror=true;
      }
    
    if (!formerror) {
      
      this.loading = true;
	  
	  this.materialService.updateData(this.form.value)
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
