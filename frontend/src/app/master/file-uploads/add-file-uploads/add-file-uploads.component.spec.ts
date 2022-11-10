import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { AddFileUploadsComponent } from './add-file-uploads.component';

describe('AddFileUploadsComponent', () => {
  let component: AddFileUploadsComponent;
  let fixture: ComponentFixture<AddFileUploadsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ AddFileUploadsComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(AddFileUploadsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
