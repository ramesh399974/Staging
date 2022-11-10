import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { EditFileUploadsComponent } from './edit-file-uploads.component';

describe('EditFileUploadsComponent', () => {
  let component: EditFileUploadsComponent;
  let fixture: ComponentFixture<EditFileUploadsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ EditFileUploadsComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(EditFileUploadsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
