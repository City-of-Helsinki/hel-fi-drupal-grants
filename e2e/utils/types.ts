export type Role = 'REGISTERED_COMMUNITY' | 'UNREGISTERED_COMMUNITY' | 'PRIVATE_PERSON';

type ATVDocument = {
  id: string;
  type: string;
  service: string;
  transaction_id: string;
};

export type PaginatedDocumentlist = {
  count: number;
  next: string | null;
  previous: string | null;
  results: ATVDocument[];
};

export type UserInputData = Record<string, string>;
