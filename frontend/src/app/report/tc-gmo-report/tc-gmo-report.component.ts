import { Component, OnInit,EventEmitter,QueryList, ViewChildren } from '@angular/core';
import { FormGroup, FormBuilder, Validators, FormControl,FormArray } from '@angular/forms';
import { ActivatedRoute ,Params, Router } from '@angular/router';
import { AuthenticationService } from '@app/services/authentication.service';
import { first } from 'rxjs/operators';
import {Observable} from 'rxjs';
import {saveAs} from 'file-saver';
import {NgbModal, ModalDismissReasons, NgbModalOptions} from '@ng-bootstrap/ng-bootstrap';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';
import { User } from '@app/models/master/user';
import { UserService } from '@app/services/master/user/user.service';
import { TcReportService } from '@app/services/report/tc-report.service';


@Component({
  selector: 'app-tc-gmo-report',
  templateUrl: './tc-gmo-report.component.html',
  styleUrls: ['./tc-gmo-report.component.scss']
})
export class TcGmoReportComponent implements OnInit {

  title = 'TC GMO REPORT';
  form : FormGroup;
  loading = false;
  buttonDisable = false;
  maxDate = new Date();
  error:any;
  success:any;
  data:any=[];
  appdata:any=[];
  modalss:any;
  userType:number;
  userdetails:any;
  userdecoded:any;

  constructor(private modalService: NgbModal,
     private userservice: UserService,
     private router: Router,
     private fb:FormBuilder, 
     private authservice:AuthenticationService,
     private errorSummary: ErrorSummaryService,
     public service: TcReportService) { }

  ngOnInit() {

    this.form = this.fb.group({
      from_date:[''],
      to_date:[''],
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

  get f() { return this.form.controls; } 

  onchangeHandler()
  {
    this.data=[];
  }

  openmodal(content,arg='') {
    this.modalss = this.modalService.open(content, {ariaLabelledBy: 'modal-basic-title',centered: true});
  }

  fieldErrors:any;
  from_dateErr:any;
  to_dateErr:any;
  form_validate_error:any;

  onSubmit(type,filename='')
  {

    this.f.from_date.markAsTouched();
    this.f.to_date.markAsTouched();

    this.fieldErrors='';
    this.from_dateErr='';
    this.to_dateErr='';

    let from_date = this.form.get('from_date').value;
    let to_date = this.form.get('to_date').value;

    if((from_date=='' || from_date===null) && (to_date=='' || to_date===null))
    {
      this.fieldErrors="Please add From and To Dates";
      return false;
    }

    let expobject:any={from_date:from_date,to_date:to_date,type:type};

    if(1)
      {
        if(type=='submit')
        {
          this.data=[];
          this.loading = true;
          this.service.getGMOData(expobject)
          .pipe(first())
          .subscribe(res => {        
              if(res.status){
                this.data = res.gmoreports;
                this.buttonDisable = false;
              }else if(res.status == 0){
                this.error = {summary:res};
              }
              this.loading = false;
              this.buttonDisable = false;
          },
          error => {
              this.error = {summary:error};
              this.loading = false;
              this.buttonDisable = false;
          });
        }
        else
        {
          this.service.downloadGMOFile(expobject)
          .pipe(first())
          .subscribe(res => {        
            this.modalss.close();
            let fileextension = filename.split('.').pop(); 
            let contenttype = this.errorSummary.getContentType(filename);
            saveAs(new Blob([res],{type:contenttype}),filename);
          },
          error => {
              this.error = {summary:error};
          });
        }
        
        
      } else {
        
        this.error = {summary:this.errorSummary.errorSummaryText};
        //this.errorSummary.validateAllFormFields(this.form); 
        
      }

    
  }

}
