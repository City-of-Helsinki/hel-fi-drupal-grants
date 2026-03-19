import type { RJSFSchema, UiSchema as RJSFUiSchema } from '@rjsf/utils';

// biome-ignore lint/suspicious/noExplicitAny: RJSF uses any for form data.
export type UiSchema = RJSFUiSchema<any, RJSFSchema, any>;
