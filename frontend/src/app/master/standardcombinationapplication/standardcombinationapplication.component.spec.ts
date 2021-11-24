import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { StandardcombinationapplicationComponent } from './standardcombinationapplication.component';

describe('StandardcombinationapplicationComponent', () => {
  let component: StandardcombinationapplicationComponent;
  let fixture: ComponentFixture<StandardcombinationapplicationComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ StandardcombinationapplicationComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(StandardcombinationapplicationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
