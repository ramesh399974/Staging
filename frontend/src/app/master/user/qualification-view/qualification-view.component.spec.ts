import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { QualificationViewComponent } from './qualification-view.component';

describe('QualificationViewComponent', () => {
  let component: QualificationViewComponent;
  let fixture: ComponentFixture<QualificationViewComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ QualificationViewComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(QualificationViewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
