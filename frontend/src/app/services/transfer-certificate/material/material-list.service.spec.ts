import { TestBed } from '@angular/core/testing';

import { MaterialListService } from './material-list.service';

describe('MaterialListService', () => {
  beforeEach(() => TestBed.configureTestingModule({}));

  it('should be created', () => {
    const service: MaterialListService = TestBed.get(MaterialListService);
    expect(service).toBeTruthy();
  });
});
