import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders,HttpParams } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { environment } from '@environments/environment';


@Injectable({
  providedIn: 'root'
})
export class MaterialService {

  constructor(private http: HttpClient) { }
  httpOptions = {
    headers: new HttpHeaders({
      'Content-Type': 'application/json'
    })
  }
      
  getMaterial(id): Observable<any>{
    return this.http.post<any>(`${environment.apiUrl}/transfercertificate/material/view`,{id});
  }
  
  getMaterialList(): Observable<any>{
    return this.http.get<any>(`${environment.apiUrl}/transfercertificate/material/index`);
  }  
  
  updateData(formData): Observable<any>{
    return this.http.post<any>(`${environment.apiUrl}/transfercertificate/material/update`, formData,this.httpOptions);
  }
  
  addData(data){
    return this.http.post<any>(`${environment.apiUrl}/transfercertificate/material/create`, data);
  } 
}
