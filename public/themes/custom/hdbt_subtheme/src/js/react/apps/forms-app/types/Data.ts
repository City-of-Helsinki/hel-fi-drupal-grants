import type { RJSFSchema, UiSchema } from '@rjsf/utils/lib';
import type { GrantsProfile } from './GrantsProfile';
import type { RJSFFormData } from './RJSFFormData';

export type FormConfig = {
  actingYears?: string[];
  applicationNumber: string;
  grantsProfile: GrantsProfile;
  persistedData: RJSFFormData;
  token: string;
  schema: RJSFSchema;
  settings: { [key: string]: string };
  submitState: string;
  subventionFields: string[];
  summaryData?: {
    applicationNumber?: string;
    applicationSubmitted?: string;
    attachments?: string[];
    handlers?: Array<string[]>;
    statusUpdates?: string[];
  };
  requiredFileFields: string[];
  translations: {
    [key in 'fi' | 'sv' | 'en']: { [key: string]: string };
  };
  uiSchema: UiSchema;
};

export type ResponseData = Omit<FormConfig, 'grantsProfile' | 'uiSchema' | 'submit_state'> & {
  applicationNumber: string;
  grants_profile: GrantsProfile;
  settings: {
    acting_years?: string[];
  };
  status: string;
  summary_data?: {
    application_number?: string;
    application_submitted?: string;
    attachments?: string[];
    handlers?: Array<string[]>;
    status_updates?: string[];
  };
  ui_schema: UiSchema;
};
