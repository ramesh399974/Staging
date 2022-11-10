import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ListFileUploadsComponent } from './list-file-uploads.component';

describe('ListFileUploadsComponent', () => {
  let component: ListFileUploadsComponent;
  let fixture: ComponentFixture<ListFileUploadsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ListFileUploadsComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ListFileUploadsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
