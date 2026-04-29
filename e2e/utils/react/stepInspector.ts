/**
 * The full form organised as a nested map.
 *
 * The three levels are: step → section → fieldName.
 * Use this to look up a field by where it lives in the form
 * without having to search a flat list.
 */
export type FormTree = Record<string, Record<string, Record<string, StepField>>>;

/**
 * All the metadata a test needs about a single form field.
 *
 * Holds the field's location in the form, its type, label
 * translation key, validation rules, and any tooltip or
 * conditional-display information.
 */
export type StepField = {
  step: string;
  section: string;
  sectionTitle: string;
  fieldName: string;
  fieldPath: string[];
  titleKey: string;
  title: string;
  descriptionKey?: string;
  description?: string;
  type: string;
  widget?: string;
  required: boolean;
  options?: Array<{ id: number | string; label: string }>;
  conditional?: boolean;
  conditionField?: string;
  isArrayItem?: boolean;
  arrayField?: string;
  addButtonTextKey?: string;
  groupDescriptionKey?: string;
  tooltipLabel?: string;
  tooltipButtonLabel?: string;
  tooltipText?: string;
};

/**
 * The parts of the form preview response this file needs.
 *
 * A narrower version of FormPreviewResponse so this file
 * does not depend on the full API response shape.
 */
type FormData = {
  schema: {
    definitions: Record<string, any>;
    properties: Record<string, any>;
  };
  ui_schema: Record<string, any>;
  translations: Record<string, any>;
};

/**
 * Returns a flat list of every field on a given form step.
 *
 * Reads the schema and UI schema for the step and converts
 * each field into a StepField object the tests can use.
 * Handles plain fields, nested objects, array rows, and
 * fields that only appear when a condition is met.
 *
 * @param data
 *   The form schema, UI schema, and translations.
 * @param step
 *   The step name to inspect, e.g. 'applicant_info'.
 * @param locale
 *   The language code for translations. Defaults to 'en'.
 */
export function getStepFields(data: FormData, step: string, locale = 'en'): StepField[] {
  const translations: Record<string, string> = data.translations[locale]?.translation ?? {};
  const definitions: Record<string, any> = data.schema.definitions;
  const stepDefinition = definitions[step];

  if (!stepDefinition) {
    return [];
  }

  const stepUiSchema = data.ui_schema[step] ?? {};
  const fields: StepField[] = [];

  /**
   * Follows a $ref pointer and returns the real schema object.
   *
   * JSON schemas use $ref to point to a shared definition
   * instead of repeating the same structure everywhere. This
   * swaps the pointer out for the actual definition so the
   * rest of the code never has to deal with $ref.
   *
   * @param schema
   *   The schema object that may contain a $ref pointer.
   */
  const resolveSchema = (schema: any): any => {
    if (!schema?.$ref) {
      return schema;
    }

    const definitionName = String(schema.$ref).replace('#/definitions/', '');
    return resolveSchema(definitions[definitionName]);
  };

  /**
   * Returns the translated text for a translation key.
   *
   * If no translation exists for the key, the key itself is
   * returned so a missing translation never causes a crash or
   * a silent empty label.
   *
   * @param key
   *   The translation key to look up, e.g. 'email.title'.
   */
  const translate = (key?: string): string | undefined => {
    if (!key) {
      return undefined;
    }

    return translations[key] ?? key;
  };

  /**
   * Builds a StepField object and adds it to the fields list.
   *
   * Reads the schema and UI schema for one field, translates
   * its labels, and pushes the result into the shared fields
   * array so the caller gets a complete list when it returns.
   *
   * @param fieldName
   *   The property name of the field, e.g. 'email'.
   * @param rawFieldSchema
   *   The raw schema for the field (may contain a $ref).
   * @param fieldUiSchema
   *   The UI schema entry for this field.
   * @param required
   *   True if the field is required by its parent object.
   * @param pathPrefix
   *   The path segments above this field, used to build the
   *   DOM id.
   * @param sectionName
   *   The section this field belongs to.
   * @param sectionTitle
   *   The translated title of the section.
   */
  const pushField = (
    fieldName: string,
    rawFieldSchema: any,
    fieldUiSchema: any,
    required: boolean,
    pathPrefix: string[],
    sectionName: string,
    sectionTitle: string,
  ): void => {
    const fieldSchema = resolveSchema(rawFieldSchema);
    const uiOptions = fieldUiSchema?.['ui:options'] ?? {};
    const rawOptions: Array<{ id: number | string; label: string }> | undefined = fieldSchema?.options;

    fields.push({
      step,
      section: sectionName,
      sectionTitle,
      fieldName,
      fieldPath: [...pathPrefix, fieldName],
      titleKey: fieldSchema?.title ?? '',
      title: translate(fieldSchema?.title) ?? fieldName,
      descriptionKey: fieldSchema?.description,
      description: translate(fieldSchema?.description),
      type: fieldSchema?.type ?? 'string',
      widget: fieldUiSchema?.['ui:widget'] ?? fieldUiSchema?.['ui:field'],
      required,
      options: rawOptions?.length ? rawOptions : undefined,
      tooltipLabel: uiOptions.tooltipLabel ? translate(uiOptions.tooltipLabel) : undefined,
      tooltipButtonLabel: uiOptions.tooltipButtonLabel ? translate(uiOptions.tooltipButtonLabel) : undefined,
      tooltipText: uiOptions.tooltipText ? translate(uiOptions.tooltipText) : undefined,
    });
  };

  // Walk each section of the step.
  for (const [sectionName, rawSectionSchema] of Object.entries<any>(stepDefinition.properties ?? {})) {
    const sectionSchema = resolveSchema(rawSectionSchema);
    const sectionTitle = translate(sectionSchema.title) ?? sectionName;
    const sectionUiSchema = stepUiSchema[sectionName] ?? {};
    const sectionRequiredFields = new Set<string>(sectionSchema.required ?? []);

    // Walk each field in this section.
    for (const [fieldName, rawFieldSchema] of Object.entries<any>(sectionSchema.properties ?? {})) {
      const fieldUiSchema = sectionUiSchema[fieldName] ?? {};

      if (fieldUiSchema['ui:widget'] === 'hidden') {
        continue;
      }

      const fieldSchema = resolveSchema(rawFieldSchema);

      // Object fields are containers, step into their children.
      if (fieldSchema?.type === 'object' && fieldSchema.properties) {
        const nestedRequiredFields = new Set<string>(fieldSchema.required ?? []);
        const nestedUiSchema = fieldUiSchema;

        // Walk each child property of the nested object.
        for (const [childName, childRawSchema] of Object.entries<any>(fieldSchema.properties)) {
          const childSchema = resolveSchema(childRawSchema);

          if (childSchema?.type === 'null') {
            continue;
          }

          if (childSchema?.type === 'array') {
            const arrayItemSchemas: any[] = Array.isArray(childSchema.items)
              ? childSchema.items
              : childSchema.items
                ? [childSchema.items]
                : [];

            if (
              arrayItemSchemas.length > 0 &&
              arrayItemSchemas.every((arrayItemSchema: any) => arrayItemSchema?.type === 'null')
            ) {
              continue;
            }
          }

          // Add this nested field to the results list.
          pushField(
            childName,
            childRawSchema,
            nestedUiSchema[childName] ?? {},
            nestedRequiredFields.has(childName),
            [step, sectionName, fieldName],
            sectionName,
            sectionTitle,
          );
        }
      }
      // Array fields hold repeating rows, collect each row's fields.
      else if (fieldSchema?.type === 'array') {
        const addButtonTextKey: string | undefined = fieldUiSchema?.['ui:options']?.addText;
        const itemSchema = resolveSchema(Array.isArray(fieldSchema.items) ? fieldSchema.items[0] : fieldSchema.items);
        const itemUiSchema = fieldUiSchema.items ?? {};
        const itemRequiredFields = new Set<string>(itemSchema?.required ?? []);

        // Walk each field inside one array item row.
        for (const [childName, childRawSchema] of Object.entries<any>(itemSchema?.properties ?? {})) {
          const childSchema = resolveSchema(childRawSchema);

          if (childSchema?.type === 'null') {
            continue;
          }

          pushField(
            childName,
            childRawSchema,
            itemUiSchema[childName] ?? {},
            itemRequiredFields.has(childName),
            [step, sectionName, fieldName, '0'],
            sectionName,
            sectionTitle,
          );

          const field = fields[fields.length - 1];
          field.isArrayItem = true;
          field.arrayField = fieldName;
          field.addButtonTextKey = addButtonTextKey;
          field.groupDescriptionKey = itemSchema?.description;
        }
      }
      // Plain field, add it directly.
      else {
        pushField(
          fieldName,
          rawFieldSchema,
          fieldUiSchema,
          sectionRequiredFields.has(fieldName),
          [step, sectionName],
          sectionName,
          sectionTitle,
        );
      }
    }

    // Walk fields that only appear when a condition is met.
    for (const conditionalSchema of sectionSchema.allOf ?? []) {
      if (!conditionalSchema.if || !conditionalSchema.then) {
        continue;
      }

      const conditionalRequiredFields = new Set<string>(conditionalSchema.then.required ?? []);
      const conditionField = Object.keys(conditionalSchema.if.properties ?? {})[0];

      // Walk each field that the condition reveals.
      for (const [fieldName, rawFieldSchema] of Object.entries<any>(conditionalSchema.then.properties ?? {})) {
        const fieldSchema = resolveSchema(rawFieldSchema);
        const fieldUiSchema = sectionUiSchema[fieldName] ?? {};

        // Conditional array, collect its row fields too.
        if (fieldSchema?.type === 'array') {
          const addButtonTextKey: string | undefined = fieldUiSchema?.['ui:options']?.addText;
          const itemSchema = resolveSchema(Array.isArray(fieldSchema.items) ? fieldSchema.items[0] : fieldSchema.items);
          const itemUiSchema = fieldUiSchema.items ?? {};
          const itemRequiredFields = new Set<string>(itemSchema?.required ?? []);

          for (const [childName, childRawSchema] of Object.entries<any>(itemSchema?.properties ?? {})) {
            pushField(
              childName,
              childRawSchema,
              itemUiSchema[childName] ?? {},
              itemRequiredFields.has(childName),
              [step, sectionName, fieldName, '0'],
              sectionName,
              sectionTitle,
            );

            const field = fields[fields.length - 1];
            field.conditional = true;
            field.conditionField = conditionField;
            field.isArrayItem = true;
            field.arrayField = fieldName;
            field.addButtonTextKey = addButtonTextKey;
            field.groupDescriptionKey = itemSchema?.description;
          }
        }
        // Plain conditional field, add it and mark it conditional.
        else {
          pushField(
            fieldName,
            rawFieldSchema,
            fieldUiSchema,
            conditionalRequiredFields.has(fieldName),
            [step, sectionName],
            sectionName,
            sectionTitle,
          );

          const field = fields[fields.length - 1];
          field.conditional = true;
          field.conditionField = conditionField;
        }
      }
    }
  }

  return fields;
}

/**
 * Builds a nested map of all steps, sections, and fields.
 *
 * Calls getStepFields for each step in the schema and groups
 * the results into a FormTree so tests can look up any field
 * by step and section without looping through a flat list.
 *
 * @param data
 *   The form schema, UI schema, and translations.
 * @param locale
 *   The language code for translations. Defaults to 'en'.
 */
export function buildFormTree(data: FormData, locale = 'en'): FormTree {
  const tree: FormTree = {};

  // Walk each step in the schema and collect its fields.
  for (const step of Object.keys(data.schema.properties ?? {})) {
    const fields = getStepFields(data, step, locale);

    if (fields.length === 0) {
      continue;
    }

    tree[step] = {};

    // Place each field into the tree under its section.
    for (const field of fields) {
      tree[step][field.section] ??= {};
      tree[step][field.section][field.fieldName] = field;
    }
  }

  return tree;
}
