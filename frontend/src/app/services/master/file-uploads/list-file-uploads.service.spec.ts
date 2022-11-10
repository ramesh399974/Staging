import { TestBed } from '@angular/core/testing';

import { ListFileUploadsService } from './list-file-uploads.service';

describe('ListFileUploadsService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: ListFileUploadsService = TestBed.get(ListFileUploadsService);
    expect(service).toBeTruthy();
  });
});
