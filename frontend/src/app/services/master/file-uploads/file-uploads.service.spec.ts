import { TestBed } from '@angular/core/testing';

import { FileUploadsService } from './file-uploads.service';

describe('FileUploadsService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: FileUploadsService = TestBed.get(FileUploadsService);
    expect(service).toBeTruthy();
  });
});
