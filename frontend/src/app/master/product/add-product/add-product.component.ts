import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators, FormControl,FormArray } from '@angular/forms';
import { ProductService } from '@app/services/master/product/product.service';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';
import { Router } from '@angular/router';
import { first } from 'rxjs/operators';
import { StandardService } from '@app/services/standard.service';
import { Standard } from '@app/services/standard';
@Component({
  selector: 'app-add-product',
  templateUrl: './add-product.component.html',
  styleUrls: ['./add-product.component.scss']
})
export class AddProductComponent implements OnInit {
  title = 'Add Product Category';
  btnLabel = 'Save';
  form : FormGroup;
  standardList:Standard[];
  selectedstd2:any[];
  loading = false;
  buttonDisable = false;
  error:any;
  submittedError = false;
  success:any;
  nameErrors = '';
  codeErrors = '';
  descriptionErrors = '';
  
  constructor(private router: Router,private standardservice: StandardService,private fb:FormBuilder,private productService:ProductService,private errorSummary: ErrorSummaryService) { }

  ngOnInit() {

    this.standardservice.getStandard().subscribe(res => {
      this.standardList = res['standards'];
      });
      
	this.form = this.fb.group({
      name:['',[Validators.required, this.errorSummary.noWhitespaceValidator, Validators.maxLength(255),Validators.pattern("^[a-zA-Z0-9 \'\-+%/&,().-]+$")]],      
	  code:['',[Validators.required, this.errorSummary.noWhitespaceValidator, Validators.maxLength(50),Validators.pattern("^[a-zA-Z0-9 \'\-+%/&,().-]+$")]],
    standard_id:['',[Validators.required]],  
     description:['',[this.errorSummary.noWhitespaceValidator]]      
    });
  }




  get f() { return this.form.controls; }
  getSelectedValue(type,val)
  {
     if(type='standard_id'){
      return this.standardList.find(x=> x.id==val).name;
    }
  }
  
  onSubmit(){
    //console.log(this.form.valid);
    //console.log(this.form.value);
    if (this.form.valid) {
      
      console.log(this.form.value);
      this.loading = true;
      
      this.productService.addData(this.form.value)
      .pipe(
        first()        
      )
      .subscribe(res => {
        //console.log(res);
          if(res.status){			  
            this.success = {summary:res.message};
			this.buttonDisable = true;
			setTimeout(()=>this.router.navigate(['/master/product/list']),this.errorSummary.redirectTime);
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
