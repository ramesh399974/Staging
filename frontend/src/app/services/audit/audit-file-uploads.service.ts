import { Injectable } from '@angular/core';
import { environment } from '@environments/environment';
import { HttpClient, HttpHeaders, HttpErrorResponse,HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
@Injectable({
  providedIn: 'root'
})
export class AuditFileUploadsService {

  constructor(private http:HttpClient) { }

  addRemark(remarkData)
  {
    return this.http.post<any>(`${environment.apiUrl}/audit/audit-plan/add-remark`, remarkData);
  }

  getRemarkData(data):Observable<any>{    
    return this.http.post<any>(`${environment.apiUrl}/audit/audit-plan/get-applicable-data`,data);    
  }

  getReports(data):Observable<any>{    
    return this.http.post<any>(`${environment.apiUrl}/audit/audit-plan/get-file-upload-reports`,data);    
  }

  addReports(data):Observable<any>{    
    return this.http.post<any>(`${environment.apiUrl}/audit/audit-plan/upload-audit-reports`,data);    
  }

  download(data){
    return this.http.post(`${environment.apiUrl}/audit/audit-plan/download-audit-reports`, data,
    {responseType:'arraybuffer'});
  } 
}
