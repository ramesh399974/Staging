import {DecimalPipe} from '@angular/common';
import {Directive, Component, QueryList, ViewChildren,OnInit } from '@angular/core';
import {Observable} from 'rxjs';

import { ActivatedRoute ,Params, Router } from '@angular/router';
import {Request} from '@app/models/transfer-certificate/request';
import { RequestListService } from '@app/services/transfer-certificate/request/request-list.service';
import {NgbdSortableHeader, SortEvent, PaginationList,commontxt} from '@app/helpers/sortable.directive';

import { first, min } from 'rxjs/operators';
import {NgbModal, ModalDismissReasons, NgbModalOptions} from '@ng-bootstrap/ng-bootstrap';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';
import { AuthenticationService } from '@app/services/authentication.service';
import { Standard } from '@app/services/standard';
import { StandardService } from '@app/services/standard.service';
import {saveAs} from 'file-saver';
import { User } from '@app/models/master/user';
import { UserService } from '@app/services/master/user/user.service';
import { BrandService } from '@app/services/master/brand/brand.service';
import { RawMaterialListService } from '@app/services/transfer-certificate/raw-material/raw-material-list.service';


@Component({
  selector: 'app-list-request',
  templateUrl: './list-request.component.html',
  styleUrls: ['./list-request.component.scss'],
  providers: [RequestListService, DecimalPipe]
})
export class ListRequestComponent implements OnInit {

  requests$: Observable<Request[]>;
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
  standardList:Standard[];
  companynamelist:any=[];
  model: any = {id:null,action:null};
  alertInfoMessage:any;
  alertSuccessMessage:any;
  alertErrorMessage:any;
  cancelBtn=true;
  okBtn=true;

  userType:number;
  userdetails:any;
  userdecoded:any;
  franchiseList:User[];
  brandList: any=[];
  invoicetypelist: any;

  constructor(public service: RequestListService,public rmlservice: RawMaterialListService,private modalService: NgbModal,private errorSummary: ErrorSummaryService, private authservice:AuthenticationService,private standardservice: StandardService,private router:Router,private userservice: UserService,private brandservice: BrandService) {
    this.requests$ = service.request$;
    this.total$ = service.total$;
    
    this.modalOptions = this.errorSummary.modalOptions;	

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
  arrEnumStatus:any;
  statuslist:any;
  ngOnInit() {
    this.service.getFilterStatus().pipe(first())
    .subscribe(res => {
      this.arrEnumStatus = res.enumstatus;
      this.statuslist = res.statuslist;
      this.invoicetypelist = res.invoice_type_list;
    },
    error => {
        //this.error = {summary:error};
        //this.loading = false;
    });
    
    this.standardservice.getStandard().subscribe(res => {
      this.standardList = res['standards'];     
    });

    this.brandservice.getData().subscribe(res => {
      this.brandList = res.data;
    });

	this.userservice.getAllUser({type:3}).pipe(first())
	.subscribe(res => {
	  this.franchiseList = res.users;
	},
	error => {
		this.error = {summary:error};
	});

  this.rmlservice.getCompanyNames().subscribe(data=>{
    this.companynamelist  = data.companynames;
  });
  }

  countDownforFasttrack(data){
    let fasttrack_due = new Date(data.fasttrack_due).getTime();
    let current_time  = new Date().getTime();
    
    // current_time = current_time.toUTCString();
    
    // current_time = new Date(current_time).getTime();
    current_time = Math.floor(current_time/1000);
   
    let timeleft = fasttrack_due-current_time;

    let days = Math.floor(timeleft / (  60 * 60 * 24));
    let hours = Math.floor((timeleft % ( 60 * 60 * 24)) / ( 60 * 60));
    let minutes = Math.floor((timeleft % ( 60 * 60)) / ( 60));
    let seconds = Math.floor((timeleft % ( 60)));
    console.log(current_time + " " + timeleft + " " + fasttrack_due)
    if(timeleft < 0){
      this.alertErrorMessage = "Expired";
    }else{
      this.alertErrorMessage = "Remaining Time : " + days + "d " + hours + "h " + minutes + "m " + seconds +"s ";
    }
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

  openmodal(content,arg='') {
    this.modalss = this.modalService.open(content, {ariaLabelledBy: 'modal-basic-title',centered: true});
  }

  DownloadFile(val,tcfilename)
  {
    this.loading  = true;
    this.service.downloadFile({id:val})
     .pipe(first())
     .subscribe(res => {
      this.loading = false;
      this.modalss.close();
      saveAs(new Blob([res],{type:'application/pdf'}),tcfilename);
    },
    error => {
      this.error = error;
      this.loading = false;
      this.modalss.close();
    });
  }

  open(content,action,id,data='') {
    this.model.id = id;	
    this.model.action = action;	
    this.cancelBtn=true;
    this.okBtn=true;
    this.alertErrorMessage = '';
    this.alertSuccessMessage = '';
    
    if(action=='activate'){		
       this.alertInfoMessage='Are you sure, do you want to activate?';
    }else if(action=='deactivate'){		
       this.alertInfoMessage='Are you sure, do you want to deactivate?';
    }else if(action=='delete'){		
       this.alertInfoMessage='Are you sure, do you want to delete?';	
    }else if(action=='copy'){   
       this.alertInfoMessage='Are you sure, do you want to clone this TC Request?';  
    }else if(action=='alert'){
      
      this.countDownforFasttrack(data);
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
        actionStatus=2;	
      }

      if(reason=='copy'){
        this.clonerequestData();
      }else{
        this.commonUpdateData(actionStatus);
      }
      
    }  
   
    commonUpdateData(actionStatus) {
      
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
        setTimeout(()=>this.modalss.close('deactivate'),this.errorSummary.redirectTime);
        this.service.searchTerm=this.service.searchTerm;
          }else if(res.status == 0){			
        this.alertInfoMessage='';
              this.alertErrorMessage = res.message;	
        }else{
        this.alertInfoMessage='';
              this.alertErrorMessage = res.message;
      }				
      },
      error => {
      this.alertInfoMessage='';
          this.alertErrorMessage = error;
         
      });
    }

    clonerequestData() {
      
      this.alertInfoMessage='Please wait. Your request is processing';
      this.service.clonerequestData({id:this.model.id}).pipe(first())
        .subscribe(res => {
        this.model.id = null;
        this.model.action = null;
        this.cancelBtn=false;
        this.okBtn=false;
        
          if(res.status){
            this.alertInfoMessage='';
            this.alertSuccessMessage = res.message;
            setTimeout(()=>{
              this.modalss.close('');
              this.router.navigateByUrl('/transfer-certificate/request/edit?id='+res.newid); 
              },this.errorSummary.redirectTime);
            this.service.searchTerm=this.service.searchTerm;
          }else if(res.status == 0){      
            this.alertInfoMessage='';
            this.alertErrorMessage = res.message; 
          }else{
            this.alertInfoMessage='';
            this.alertErrorMessage = res.message;
        }       
        },
        error => {
          this.alertInfoMessage='';
          this.alertErrorMessage = error;
         
      });
    }  
   
    getSelectedValue(val)
    {
      return this.standardList.find(x=> x.id==val).code;    
    }

    getSelectedBrandValue(val)
    {
      return this.brandList.find(x=> x.id==val).brand_name;    
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
