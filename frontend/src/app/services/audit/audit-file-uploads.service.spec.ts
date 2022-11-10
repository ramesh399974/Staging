import { TestBed } from '@angular/core/testing';

import { AuditFileUploadsService } from './audit-file-uploads.service';

describe('AuditFileUploadsService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: AuditFileUploadsService = TestBed.get(AuditFileUploadsService);
    expect(service).toBeTruthy();
  });
});
