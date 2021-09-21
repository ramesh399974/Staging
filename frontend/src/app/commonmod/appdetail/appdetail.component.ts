import { Component, OnInit, Input } from '@angular/core';
import { EnquiryDetailService } from '@app/services/enquiry-detail.service';
import { ApplicationDetailService } from '@app/services/application/list/application-detail.service';
import { Application } from '@app/models/application/application';
import {saveAs} from 'file-saver';
import {NgbModal, ModalDismissReasons} from '@ng-bootstrap/ng-bootstrap';
import { first } from 'rxjs/operators';
import { ErrorSummaryService } from '@app/helpers/errorsummary.service';
import { AuthenticationService } from '@app/services';

@Component({
  selector: 'app-appdetail',
  templateUrl: './appdetail.component.html',
  styleUrls: ['./appdetail.component.scss']
})
export class AppdetailComponent implements OnInit {
  //@Input() applicationdata: Application;
  @Input() userdecoded: any;
  @Input() id: number;
  @Input() showtype: any;
  @Input() display_type: any;

  panelOpenState = false;
  childmodel:any = {user_bsector_group_id:''};
  detailForm : any = {};
  applicationdata:Application;
  apploading:any;
  error:any;
  userType:number;
  userdetails:any;
  
  constructor(private authservice:AuthenticationService,private modalService: NgbModal, private enquiryDetail:EnquiryDetailService,private applicationDetail:ApplicationDetailService, private errorSummary: ErrorSummaryService) { }

  modalss:any;
  open(content,arg='') {
    this.modalss = this.modalService.open(content, {ariaLabelledBy: 'modal-basic-title',centered: true});
  }

  downloadCompanyFile(filename){
    this.enquiryDetail.downloadCompanyFile({id:this.id})
    .subscribe(res => {
      this.modalss.close();
      let fileextension = filename.split('.').pop(); 
      let contenttype = this.enquiryDetail.docsContentType[fileextension];
      saveAs(new Blob([res],{type:contenttype}),filename);
     
    });
  }
  downloadcertificateFile(fileid,filename){
    this.enquiryDetail.downloadcertificateFile({id:fileid})
    .subscribe(res => {
      
      let fileextension = filename.split('.').pop(); 
      let contenttype = this.errorSummary.getContentType(filename);
      saveAs(new Blob([res],{type:contenttype}),filename);
      this.modalss.close('');
    });
  }

   downloadChecklistFile(fileid,filename){
    this.enquiryDetail.downloadChecklistFile({id:fileid})
    .subscribe(res => {
      this.modalss.close();
      let fileextension = filename.split('.').pop(); 
      let contenttype = this.errorSummary.getContentType(filename);
      saveAs(new Blob([res],{type:contenttype}),filename);
     
    });
  }

  downloadFile(fileid,filename){
    this.enquiryDetail.downloadFile({id:fileid})
    .subscribe(res => {
      this.modalss.close();
      let fileextension = filename.split('.').pop(); 
      let contenttype = this.enquiryDetail.docsContentType[fileextension];
      saveAs(new Blob([res],{type:contenttype}),filename);
     
    });
  }

  


  ngOnInit() 
  {
    this.apploading=true;
    
    let dataobj:any = 'id='+this.id+'&actiontype=view&showtype='+(this.showtype==undefined ? '' : this.showtype);
    this.applicationDetail.getApplicationDetailsByGet(dataobj)
    .pipe(first())
    .subscribe(res => {
      this.applicationdata = res;
      this.apploading=false;
    },
    error => {
        this.error = {summary:error};
        this.apploading=false;
    });

    this.authservice.currentUser.subscribe(x => {
      if(x){
        
         
        let user = this.authservice.getDecodeToken();
        this.userType= user.decodedToken.user_type;
        this.userdetails= user.decodedToken;
        //console.log(this.userdetails);
        
      }else{
        this.userdecoded=null;
      }
    });
  
  }

}
