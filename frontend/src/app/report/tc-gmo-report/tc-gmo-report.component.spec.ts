import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { TcGmoReportComponent } from './tc-gmo-report.component';

describe('TcGmoReportComponent', () => {
  let component: TcGmoReportComponent;
  let fixture: ComponentFixture<TcGmoReportComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ TcGmoReportComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(TcGmoReportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
