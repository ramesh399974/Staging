import { Component, OnInit } from '@angular/core';
import { FormGroup, FormBuilder, Validators, FormControl,FormArray } from '@angular/forms';
import { MaterialCompositionService } from '@app/services/master/materialcomposition/materialcomposition.service';
import { ProductService } from '@app/services/master/product/product.service';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { tap,first } from 'rxjs/operators';
import { Product } from '@app/models/master/product';
import { ProductType } from '@app/models/master/producttype';
import { StandardService } from '@app/services/standard.service';
import { MaterialType } from '@app/models/master/materialtype';

@Component({
  selector: 'app-edit-materialcomposition',
  templateUrl: '../add-materialcomposition/add-materialcomposition.component.html',
  styleUrls: ['./edit-materialcomposition.component.scss']
})
export class EditMaterialcompositionComponent implements OnInit {

  title = 'Edit Material';
  btnLabel = 'Update';
  id:number;
  productList:Product[];
  productTypeList:ProductType[];
  form : FormGroup;
  loading = false;
  buttonDisable = false;
  error:any;
  submittedError = false;
  success:any;
  product_idErrors = '';
  product_type_idErrors = '';
  nameErrors = ''; 
  codeErrors = '';
  
  formData:FormData = new FormData();
  standardList: any;
  standardids: any=[];
  materialTypeList:MaterialType[]=[];
  
  constructor(private standards: StandardService,private activatedRoute:ActivatedRoute,private router: Router,private fb:FormBuilder,private productService: ProductService,private materialCompositionService:MaterialCompositionService,private errorSummary: ErrorSummaryService) { }

  ngOnInit() {
	  
	this.id = this.activatedRoute.snapshot.queryParams.id;
		
	this.productService.getProductList().subscribe(res => {
      this.productList = res['products'];
      this.materialTypeList = res['material_type'];      
    });
    
    this.standards.getStandard().subscribe(res =>{
      this.standardList = res['standards'];
    })
	
	this.form = this.fb.group({
	  id:[''],
      product_id:['',[Validators.required]], 
	  product_type_id:['',[Validators.required]],  
      name:['',[Validators.required, this.errorSummary.noWhitespaceValidator, Validators.maxLength(255), Validators.pattern("^[a-zA-Z0-9 \'\-+%/&,().-]+$")]],
	  code:['',[Validators.required, this.errorSummary.noWhitespaceValidator, Validators.maxLength(50),Validators.pattern("^[a-zA-Z0-9 \'\-+%/&,().-]+$")]],
	  description:['',[this.errorSummary.noWhitespaceValidator]],
    standard_id:['',[Validators.required]], 
    material_type:['',[Validators.required]],
    });
	
	this.materialCompositionService.getMaterialComposition(this.id).pipe(first(),
		tap(res=>{
			if(res.product_id){
			  this.getProductTypeList(res.product_id);
			}        
		})
	)
    .subscribe(res => {
      let materialComposition = res.data;

      res.std.forEach(val =>{
        this.standardids.push(""+val+"");
      })
      
	  this.getProductTypeList(materialComposition.product_id);
      this.form.patchValue(materialComposition);
      this.form.patchValue({
        standard_id : this.standardids,
        material_type : materialComposition.material_qua?materialComposition.material_qua:''
      })
    },
    error => {
        this.error = error;
        this.loading = false;
    });
	
  }
  
  getProductTypeList(id:number){
	this.productTypeList=[];
	this.form.patchValue({product_type_id:''});
	if(id>0)
	{
		this.productService.getProductType(id).subscribe(res => {
		  if(res['status'])
		  {
			this.productTypeList = res['data'];
		  }
		});
	}
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
    this.f.product_id.markAsTouched();
    this.f.product_type_id.markAsTouched();
    this.f.name.markAsTouched();
    this.f.code.markAsTouched();
    this.f.material_type.markAsTouched();

    let product_id = this.form.get('product_id').value;
    let product_type_id = this.form.get('product_type_id').value;
    let name = this.form.get('name').value;
    let code = this.form.get('code').value;
    let material_type = this.form.get('material_type').value;
    let standard_id = this.form.get('standard_id').value;

    if(product_id=='' || product_type_id =='' || name =='' || code =='' || material_type==''){
      formerror=true;
    }
    if(material_type==1){
      this.f.standard_id.markAsTouched();
      if(standard_id.length==0){
        formerror=true;
      }
    }
    if (!formerror) {
      
      this.loading = true;
	  
	  this.materialCompositionService.updateData(this.form.value)
      .pipe(
        first()        
      ).subscribe(res => {
          if(res.status){
            this.success = {summary:res.message};
			this.buttonDisable = true;
            setTimeout(()=>this.router.navigate(['/master/materialcomposition/list']),this.errorSummary.redirectTime);            
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