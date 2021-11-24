import { Injectable } from '@angular/core';
import { SwUpdate } from '@angular/service-worker';
import { interval } from 'rxjs';
import Swal from 'sweetalert2'

const swalWithBootstrapButtons = Swal.mixin({
    customClass: {confirmButton: 'btn btn-success',cancelButton: 'btn btn-danger'},
     buttonsStyling: false
  })
  @Injectable({
    providedIn: 'root',
  })
export class UpdateService {
    constructor(private swUpdate: SwUpdate) {}

      updateClient() {
        if (!this.swUpdate.isEnabled) {
          console.log('SWUpdate Not Enabled');
          return;
        }
        this.swUpdate.available.subscribe((event) => {
          //console.log(`current`, event.current, `available `, event.available);
          swalWithBootstrapButtons.fire({
            title: 'Update',
            text: "New Version Of Portal is Available",
            icon: 'info',
            confirmButtonText: 'Update',
            allowOutsideClick: false,
            reverseButtons: true
          }).then((result) => {
            if (result.isConfirmed) {
              // Reload code go here 
              this.swUpdate.activateUpdate().then(() => location.reload());
            }
          })
           
        });
    
        this.swUpdate.activated.subscribe((event) => {
          //console.log(`current`, event.previous, `available `, event.current);
        });
      }
    
      checkUpdate() {
            const timeInterval = interval(6 * 60 * 60);
            timeInterval.subscribe(() => {
              this.swUpdate.checkForUpdate().then(() => {this.updateClient()});
              //console.log('update checked');
            });      
      }
}