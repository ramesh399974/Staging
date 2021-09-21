import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ListCertificateComponent } from './list-certificate.component';

describe('ListCertificateComponent', () => {
  let component: ListCertificateComponent;
  let fixture: ComponentFixture<ListCertificateComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ListCertificateComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ListCertificateComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
