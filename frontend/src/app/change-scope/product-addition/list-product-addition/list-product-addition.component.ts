import {DecimalPipe} from '@angular/common';
import {Directive, Component, QueryList, ViewChildren } from '@angular/core';
import {Observable} from 'rxjs';

import {Productaddition} from '@app/models/changescope/Productaddition';
import { ProductAdditionListService } from '@app/services/change-scope/product-addition-list.service';
import {NgbdSortableHeader, SortEvent, PaginationList,commontxt} from '@app/helpers/sortable.directive';
import { UserService } from '@app/services/master/user/user.service';
import { User } from '@app/models/master/user';

import { first } from 'rxjs/operators';
import {NgbModal, ModalDismissReasons, NgbModalOptions} from '@ng-bootstrap/ng-bootstrap';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';
import { AuthenticationService } from '@app/services/authentication.service';

@Component({
  selector: 'app-list-product-addition',
  templateUrl: './list-product-addition.component.html',
  styleUrls: ['./list-product-addition.component.scss'],
  providers: [ProductAdditionListService, DecimalPipe]
})
export class ListProductAdditionComponent{

  productadditions$: Observable<Productaddition[]>;
  total$: Observable<number>;
  paginationList = PaginationList;
  commontxt = commontxt;
  @ViewChildren(NgbdSortableHeader) headers: QueryList<NgbdSortableHeader>;

  closeResult: string;
  modalOptions:NgbModalOptions;
  loading = false;
  buttonDisable = false;
  error:any;
  id:number;
  success:any;
  modalss:any;
  statuslist:any=[];  
  franchiseList:User[];

  model: any = {id:null,action:null};
  alertInfoMessage:any;
  alertSuccessMessage:any;
  alertErrorMessage:any;
  cancelBtn=true;
  okBtn=true;

  userType:number;
  userdetails:any;
  userdecoded:any;

  constructor(public service: ProductAdditionListService,private userservice: UserService,private modalService: NgbModal,private errorSummary: ErrorSummaryService, private authservice:AuthenticationService) 
  {
    this.productadditions$ = service.productadditions$;
    this.total$ = service.total$;
    
    this.modalOptions = this.errorSummary.modalOptions;	


    this.service.ProductAdditionStatus().subscribe(data=>{
      console.log(data.status);
      this.statuslist  = data.status;
    });

    this.userservice.getAllUser({type:3}).pipe(first())
	.subscribe(res => {
	  this.franchiseList = res.users;
	},
	error => {
		this.error = {summary:error};
	}); 



    this.authservice.currentUser.subscribe(x => {
      if(x){
     
        let user = this.authservice.getDecodeToken();
        this.userType= user.decodedToken.user_type;
        this.userdetails= user.decodedToken;
        
      }else{
        this.userdecoded=null;
      }
    });
  }

  
  onSort({column, direction}: SortEvent) {
    // resetting other headers
    //console.log('sdfsdfdsf');
    this.headers.forEach(header => {
      if (header.sortable !== column) {
        header.direction = '';
      }
    });

    this.service.sortColumn = column;
    this.service.sortDirection = direction;
  }

  open(content,action,id) {
    this.model.id = id;	
    this.model.action = action;	
    this.resetBtn();
    if(action=='activate'){		
       this.alertInfoMessage='Are you sure, do you want to activate?';
    }else if(action=='deactivate'){		
       this.alertInfoMessage='Are you sure, do you want to deactivate?';
    }else if(action=='delete'){		
       this.alertInfoMessage='Are you sure, do you want to delete?';	
    }
    
    this.modalss = this.modalService.open(content, this.modalOptions);
      //this.modalService.open(content, this.modalOptions).result.then((result) => {
    
    this.modalss.result.then((result) => {	
        this.closeResult = `Closed with: ${result}`;	 
      }, (reason) => {
      this.model.id = null;  
      this.closeResult = `Dismissed ${this.getDismissReason(reason)}`;	  
      });
  }

  private getDismissReason(reason: any): string {
    if (reason === ModalDismissReasons.ESC) {
      return 'by pressing ESC';
    } else if (reason === ModalDismissReasons.BACKDROP_CLICK) {
      return 'by clicking on a backdrop';
    } else {
      return  `with: ${reason}`;
    }
  }

  commonModalAction(){
    let reason = this.model.action;	
    
    let actionStatus=0;
    if(reason=='activate'){		
      actionStatus=0;
    }else if(reason=='deactivate'){		
      actionStatus=1;
    }else if(reason=='delete'){		
      actionStatus=reason;	
    }else{
      this.modalss.close('deactivate')
    }	
    this.commonUpdateData(actionStatus);
    }  
    buttonshow = true;
    commonUpdateData(actionStatus) {
      this.buttonshow=false;
    this.alertInfoMessage='Please wait. Your request is processing';
    this.service.commonActionData({id:this.model.id}).pipe(first())
      .subscribe(res => {
      this.model.id = null;
      this.model.action = null;
      this.cancelBtn=false;
      this.okBtn=false;
      
      if(res.status){
        this.alertInfoMessage='';
              this.alertSuccessMessage = res.message;
        setTimeout(()=>{
        this.buttonshow=true;
        this.modalss.close('deactivate');
        },this.errorSummary.redirectTime);
        this.service.searchTerm=this.service.searchTerm;
          }else if(res.status == 0){	
          this.buttonshow=true;		
        this.alertInfoMessage='';
              this.alertErrorMessage = res.message;	
        }else{
        this.buttonshow=true;
        this.alertInfoMessage='';
              this.alertErrorMessage = res.message;
      }				
      },
      error => {
      this.alertInfoMessage='';
          this.alertErrorMessage = error;
         this.buttonshow=true;
      });
    }  
    
    resetBtn()
    {
    this.alertInfoMessage='';
    this.alertSuccessMessage='';
    this.alertErrorMessage='';
    this.cancelBtn=true;
    this.okBtn=true;
    }

    getSelectedFranchiseValue(val)
  {
    return this.franchiseList.find(x=> x.id==val).osp_details;    
  }

}

