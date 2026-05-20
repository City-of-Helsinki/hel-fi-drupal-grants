import type { RJSFSchema, UiSchema } from '@rjsf/utils/lib';
import type { GrantsProfile } from './GrantsProfile';
import type { RJSFFormData } from './RJSFFormData';

export type FormSettings = {
  acting_years?: number[];
  applicant_type?: string;
  title?: string;
  [key: string]: unknown;
};

export type FormConfig = {
  actingYears?: number[];
  applicantType?: string;
  applicationNumber: string;
  grantsProfile: GrantsProfile;
  persistedData: RJSFFormData;
  token: string;
  schema: RJSFSchema;
  settings: FormSettings;
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
  settings: FormSettings;
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
