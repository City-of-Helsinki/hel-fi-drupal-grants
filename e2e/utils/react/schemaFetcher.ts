import { communitySettings, confirmAndSubmitSettings } from './formConstants';
import { expect } from '@playwright/test';
import { logger } from "../logger";
import { fetchJsonApiRequest } from "./fetchJsonApiRequest";

/**
 * The full response shape returned by the form preview API endpoint.
 * Contains the form schema, UI settings, translations, and metadata
 * needed to render and test the form.
 */
export type FormPreviewResponse = {
  settings: {
    form_identifier: string;
    application_type_id: number;
    acting_years: number[];
    applicant_types: string[];
  };
  schema: {
    definitions: Record<string, unknown>;
    properties: Record<string, unknown>;
    required?: string[];
  };
  ui_schema: Record<string, unknown>;
  translations: Record<string, { translation: Record<string, string> }>;
  form_data: unknown;
  grants_profile: unknown;
  status: string;
  token: string;
};

/**
 * Fetches the form schema from the API and crafts the schema.
 *
 * The function injects the applicant_info and confirmation_step,
 * which are not included in the API response, but are required
 * for the full form to work correctly.
 *
 * Returns only the parts of the response the tests need: the schema,
 * UI schema, and translations.
 *
 * @param formID
 *   The expected form identifier, used to verify the correct form loaded.
 * @param pathToJson
 *   The API path to fetch the form preview data from.
 */
export async function craftSchema(
  formID: string,
  pathToJson: string
): Promise<Pick<FormPreviewResponse, 'schema' | 'ui_schema' | 'translations'>> {
  const data = await fetchJsonApiRequest<FormPreviewResponse>(pathToJson);
  expect(data).toHaveProperty('settings');
  expect(data.settings.form_identifier).toBe(formID);

  if (data.settings.form_identifier !== formID) {
    logger(`Wrong form, expected ${formID}, got ${data.settings.form_identifier}`);
    return;
  }

  // Inject the applicant info and confirmation steps into the schema.
  // The API does not return these, so we add them from local constants.
  const [rootProperty, definition, uiSchemaAdditions] = communitySettings;
  const [confirmRootProperty, confirmDefinition, confirmUiSchema] = confirmAndSubmitSettings;
  const formSchema: any = {
    ...data.schema,
    definitions: {
      applicant_info: definition,
      confirm_and_submit: confirmDefinition,
      ...data.schema.definitions,
    },
    properties: {
      applicant_info: rootProperty,
      ...data.schema.properties,
      confirm_and_submit: confirmRootProperty,
    },
  };
  const formUiSchema: any = { ...data.ui_schema, ...uiSchemaAdditions, ...confirmUiSchema };
  return {
    schema: formSchema,
    ui_schema: formUiSchema,
    translations: data.translations
  };
}
