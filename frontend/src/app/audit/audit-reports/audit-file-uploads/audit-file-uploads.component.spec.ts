import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AuditFileUploadsComponent } from './audit-file-uploads.component';

describe('AuditFileUploadsComponent', () => {
  let component: AuditFileUploadsComponent;
  let fixture: ComponentFixture<AuditFileUploadsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AuditFileUploadsComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AuditFileUploadsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
