/**
 * The full form organised as a nested map.
 *
 * The three levels are: step → section → field key.
 * Use this to look up a field by where it lives in the form
 * without having to search a flat list.
 */
export type FormTree = Record<string, Record<string, Record<string, StepField>>>;

/**
 * One rule an answer must meet, like "yes" or "at least 5000".
 *
 * The path points to the answer in the form data, e.g.
 * [step, section, field].
 */
export type ConditionRule = {
  path: string[];
  const?: unknown;
  enum?: unknown[];
  minimum?: number;
  exclusiveMinimum?: number;
  maximum?: number;
  pattern?: string;
};

/**
 * A set of rules that must all be met to show a field.
 */
export type Condition = ConditionRule[];

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
  sectionTitleKey?: string;
  fieldName: string;
  fieldPath: string[];
  titleKey: string;
  title: string;
  descriptionKey?: string;
  description?: string;
  format?: string;
  type: string;
  widget?: string;
  multipleFiles?: boolean;
  fileFormats?: string[];
  simpleFile?: boolean;
  required: boolean;
  hasDefault?: boolean;
  options?: Array<{ id: number | string; label: string }>;
  enumValues?: unknown[];
  maxLength?: number;
  singleSubvention?: boolean;
  startGrant?: boolean;
  affirmativeExpands?: boolean;
  conditional?: boolean;
  conditions?: Condition[];
  active?: boolean;
  controlPaths?: string[];
  controlValue?: unknown;
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
    allOf?: any[];
  };
  ui_schema: Record<string, any>;
  translations: Record<string, any>;
};

/**
 * The list a field belongs to, when it is part of a list row.
 */
type ArrayInfo = {
  field: string;
  addButtonTextKey?: string;
  descriptionKey?: string;
};

/**
 * Turn a field path into a lookup key.
 *
 * @param path
 *   The path segments.
 */
export const pathKey = (path: string[]): string => path.join('.');

/**
 * Check whether an answer meets a condition rule.
 *
 * @param value
 *   The answer, or undefined when nothing is answered.
 * @param rule
 *   The rule to check.
 */
export function ruleMatches(value: unknown, rule: ConditionRule): boolean {
  if (value === undefined) return false;
  if ('const' in rule && value !== rule.const) return false;
  if (rule.enum && !rule.enum.includes(value)) return false;
  const amount = Number(String(value).replace(',', '.'));
  if (rule.minimum !== undefined && !(amount >= rule.minimum)) return false;
  if (rule.exclusiveMinimum !== undefined && !(amount > rule.exclusiveMinimum)) return false;
  if (rule.maximum !== undefined && !(amount <= rule.maximum)) return false;
  if (rule.pattern !== undefined && !new RegExp(rule.pattern).test(String(value))) return false;
  return true;
}

/**
 * Tell whether one answer shows or hides a conditional field.
 *
 * Returns undefined when the result also depends on other answers.
 *
 * @param conditions
 *   The field's conditions, the field shows when any of them is met.
 * @param paths
 *   The keys of the answers that are set.
 * @param value
 *   The answer given to them.
 */
export function conditionVerdict(conditions: Condition[], paths: string[], value: unknown): 'shown' | 'hidden' | undefined {
  let unknown = false;
  for (const condition of conditions) {
    const rules = condition.filter((rule) => paths.includes(pathKey(rule.path)));
    if (rules.some((rule) => !ruleMatches(value, rule))) continue;
    if (rules.length === condition.length) return 'shown';
    unknown = true;
  }
  return unknown ? undefined : 'hidden';
}

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
   * @param sectionTitleKey
   *   The translation key of the section title, if the section has one.
   * @param conditions
   *   The conditions that show the field, if any.
   * @param array
   *   The list the field belongs to, if any.
   */
  const pushField = (
    fieldName: string,
    rawFieldSchema: any,
    fieldUiSchema: any,
    required: boolean,
    pathPrefix: string[],
    sectionName: string,
    sectionTitleKey: string | undefined,
    conditions?: Condition[],
    array?: ArrayInfo,
  ): void => {
    const fieldPath = [...pathPrefix, fieldName];

    // A field shown by several conditions is listed once and shows when any of them is met.
    const existing = fields.find((field) => pathKey(field.fieldPath) === pathKey(fieldPath));
    if (existing) {
      existing.conditions = existing.conditions && conditions ? [...existing.conditions, ...conditions] : undefined;
      existing.conditional = existing.conditions !== undefined;
      return;
    }

    const fieldSchema = resolveSchema(rawFieldSchema);
    const uiOptions = fieldUiSchema?.['ui:options'] ?? {};
    const rawOptions: Array<{ id: number | string; label: string }> | undefined = fieldSchema?.options;

    fields.push({
      step,
      section: sectionName,
      sectionTitle: translate(sectionTitleKey) ?? sectionName,
      sectionTitleKey,
      fieldName,
      fieldPath,
      titleKey: fieldSchema?.title ?? '',
      title: translate(fieldSchema?.title) ?? fieldName,
      descriptionKey: fieldSchema?.description,
      description: translate(fieldSchema?.description),
      format: fieldSchema?.format,
      type: fieldSchema?.type ?? 'string',
      widget: fieldUiSchema?.['ui:widget'] ?? fieldUiSchema?.['ui:field'],
      multipleFiles: fieldUiSchema?.['misc:multiple'] === 'true' || fieldUiSchema?.['misc:multiple'] === true,
      fileFormats: Array.isArray(fieldUiSchema?.['misc:formats']) ? fieldUiSchema['misc:formats'] : undefined,
      simpleFile: fieldUiSchema?.['misc:variant'] === 'simple',
      required: required || fieldUiSchema?.['misc:required'] === true,
      hasDefault: fieldSchema?.default !== undefined,
      options: rawOptions?.length ? rawOptions : undefined,
      enumValues: Array.isArray(fieldSchema?.enum) ? fieldSchema.enum : undefined,
      // The schema carries the character limit; `misc:max-length` was the
      // uiSchema key it used to live in, kept as a fallback for older forms.
      maxLength: fieldSchema?.maxLength ?? fieldUiSchema?.['misc:max-length'],
      singleSubvention: uiOptions.useSingleSubvention === true,
      startGrant: uiOptions.startGrant != null,
      affirmativeExpands: uiOptions.affirmativeExpands === true,
      conditional: conditions !== undefined,
      conditions,
      isArrayItem: array !== undefined ? true : undefined,
      arrayField: array?.field,
      addButtonTextKey: array?.addButtonTextKey,
      groupDescriptionKey: array?.descriptionKey,
      tooltipLabel: uiOptions.tooltipLabel ? translate(uiOptions.tooltipLabel) : undefined,
      tooltipButtonLabel: uiOptions.tooltipButtonLabel ? translate(uiOptions.tooltipButtonLabel) : undefined,
      tooltipText: uiOptions.tooltipText ? translate(uiOptions.tooltipText) : undefined,
    });
  };

  // Detect fields the form does not render.
  const isHidden = (fieldUiSchema: any): boolean =>
    fieldUiSchema?.['ui:widget'] === 'hidden' || fieldUiSchema?.['ui:options']?.hidden === true;

  // Detect a group of fields, which a schema added by a condition may leave without a type.
  const isObject = (schema: any): boolean =>
    !!schema?.properties && (schema.type === undefined || schema.type === 'object');

  /**
   * Collect the rules of a condition with their full answer paths.
   *
   * @param ifSchema
   *   The condition schema.
   * @param pathPrefix
   *   The path of the object the condition belongs to.
   */
  const collectRules = (ifSchema: any, pathPrefix: string[]): Condition => {
    const rules: Condition = [];
    for (const [name, ruleSchema] of Object.entries<any>(ifSchema?.properties ?? {})) {
      if (ruleSchema?.properties) {
        rules.push(...collectRules(ruleSchema, [...pathPrefix, name]));
      } else {
        rules.push({ ...ruleSchema, path: [...pathPrefix, name] });
      }
    }
    return rules;
  };

  // Combine a parent's conditions with a nested condition, so a field inside both needs both.
  const addCondition = (conditions: Condition[] | undefined, condition: Condition): Condition[] =>
    conditions ? conditions.map((parent) => [...parent, ...condition]) : [condition];

  /**
   * Collect the fields of an object and the fields its conditions add.
   *
   * @param objectSchema
   *   The resolved object schema.
   * @param objectUiSchema
   *   The UI schema of the object.
   * @param path
   *   The path of the object.
   * @param sectionName
   *   The section the object belongs to.
   * @param sectionTitleKey
   *   The translation key of the section title, if the section has one.
   * @param isSection
   *   True when the object is a section of the step.
   * @param conditions
   *   The conditions that show the object, if any.
   * @param array
   *   The list the object belongs to, if any.
   */
  const walkObject = (
    objectSchema: any,
    objectUiSchema: any,
    path: string[],
    sectionName: string,
    sectionTitleKey: string | undefined,
    isSection: boolean,
    conditions?: Condition[],
    array?: ArrayInfo,
  ): void => {
    const requiredFields = new Set<string>(objectSchema?.required ?? []);

    for (const [fieldName, rawFieldSchema] of Object.entries<any>(objectSchema?.properties ?? {})) {
      const fieldSchema = resolveSchema(rawFieldSchema);
      const fieldUiSchema = objectUiSchema?.[fieldName] ?? {};

      if (fieldSchema?.type === 'null' || isHidden(fieldUiSchema)) {
        continue;
      }

      // Skip empty groups that only get their fields from a condition.
      if (fieldSchema?.type === 'object' && !fieldSchema.properties && !fieldUiSchema['ui:field'] && !fieldUiSchema['ui:widget']) {
        continue;
      }

      // Groups like an attachment have their own input, so only plain groups are opened.
      const isGroup = isObject(fieldSchema) && (isSection || (!fieldUiSchema['ui:field'] && !fieldUiSchema['ui:widget']));

      if (isGroup) {
        walkObject(fieldSchema, fieldUiSchema, [...path, fieldName], sectionName, sectionTitleKey, false, conditions, array);
      }
      // Lists like "Muut kulut" start with only an add button,
      // so collect the fields of the first row to add and fill it.
      else if (fieldSchema?.type === 'array') {
        const itemSchema = resolveSchema(Array.isArray(fieldSchema.items) ? fieldSchema.items[0] : fieldSchema.items);
        if (!itemSchema?.properties) {
          continue;
        }
        walkObject(itemSchema, fieldUiSchema.items ?? {}, [...path, fieldName, '0'], sectionName, sectionTitleKey, false, conditions, {
          field: [...path, fieldName].slice(2).join('_'),
          addButtonTextKey: fieldUiSchema['ui:options']?.addText,
          descriptionKey: itemSchema.description,
        });
      }
      else {
        pushField(fieldName, rawFieldSchema, fieldUiSchema, requiredFields.has(fieldName), path, sectionName, sectionTitleKey, conditions, array);
      }
    }

    // Answers like "Kyllä" reveal more fields in the same group,
    // so collect those fields together with the answers they need.
    for (const conditionalSchema of objectSchema?.allOf ?? []) {
      if (!conditionalSchema?.if || !conditionalSchema?.then) {
        continue;
      }
      const condition = collectRules(conditionalSchema.if, path);
      walkObject(resolveSchema(conditionalSchema.then), objectUiSchema, path, sectionName, sectionTitleKey, isSection, addCondition(conditions, condition), array);
    }
  };

  /**
   * Collect the fields of each section on the step.
   *
   * @param stepSchema
   *   The step schema, or the part of it a condition adds.
   * @param conditions
   *   The conditions that show the sections, if any.
   */
  const walkStep = (stepSchema: any, conditions?: Condition[]): void => {
    for (const [sectionName, rawSectionSchema] of Object.entries<any>(stepSchema?.properties ?? {})) {
      const sectionSchema = resolveSchema(rawSectionSchema);
      if (!isObject(sectionSchema)) {
        continue;
      }
      // Sections added by a condition can leave the title to the base section.
      const sectionTitleKey = sectionSchema.title ?? resolveSchema(stepDefinition.properties?.[sectionName])?.title;
      walkObject(sectionSchema, stepUiSchema[sectionName] ?? {}, [step, sectionName], sectionName, sectionTitleKey, true, conditions);
    }

    // Answers on this step can show whole sections or add fields to them.
    for (const conditionalSchema of stepSchema?.allOf ?? []) {
      if (!conditionalSchema?.if || !conditionalSchema?.then) {
        continue;
      }
      const condition = collectRules(conditionalSchema.if, [step]);
      walkStep(resolveSchema(conditionalSchema.then), addCondition(conditions, condition));
    }
  };

  walkStep(stepDefinition);

  // Answers on an earlier step can fill this step, like the rental step that
  // only has fields after choosing to apply for a rental grant.
  for (const conditionalSchema of data.schema.allOf ?? []) {
    const thenStep = conditionalSchema?.then?.properties?.[step];
    if (!conditionalSchema?.if || !thenStep) {
      continue;
    }
    walkStep(resolveSchema(thenStep), [collectRules(conditionalSchema.if, [])]);
  }

  return fields;
}

/**
 * Mark which conditional fields the chosen answers show.
 *
 * Questions that open fields with "Kyllä" are answered yes, other
 * answers come from the form's formLogic.ts.
 *
 * @param fields
 *   Every field on the form.
 * @param answers
 *   The answers from formLogic.ts, keyed by field path.
 */
function planConditions(fields: StepField[], answers: Map<string, unknown>): void {
  const conditionalFields = fields.filter((field) => field.conditions);
  const rulesByPath = new Map<string, ConditionRule[]>();

  for (const field of conditionalFields) {
    for (const rule of field.conditions!.flat()) {
      const key = pathKey(rule.path);
      rulesByPath.set(key, [...(rulesByPath.get(key) ?? []), rule]);
    }
  }

  // Answers like amounts or the second option of a choice need to be set in formLogic.ts.
  const chosen = new Map<string, unknown>();
  const missing: string[] = [];
  for (const [key, rules] of rulesByPath) {
    if (answers.has(key)) {
      chosen.set(key, answers.get(key));
    } else if (rules.some((rule) => rule.const === true || rule.enum?.includes(true))) {
      chosen.set(key, true);
    } else {
      missing.push(key);
    }
  }

  const unused = [...answers.keys()].filter((key) => !rulesByPath.has(key));
  if (missing.length > 0 || unused.length > 0) {
    throw new Error(
      [
        missing.length ? `Add answers for these conditions to formLogic.ts: ${missing.join(', ')}.` : '',
        unused.length ? `These formLogic.ts answers match no condition: ${unused.join(', ')}.` : '',
      ].filter(Boolean).join(' ')
    );
  }

  for (const field of conditionalFields) {
    field.active = field.conditions!.some((condition) =>
      condition.every((rule) => ruleMatches(chosen.get(pathKey(rule.path)), rule))
    );
  }

  // Give each question the answer to pick.
  for (const [key, value] of chosen) {
    const control = fields.find((field) => pathKey(field.fieldPath) === key);
    if (control && control.widget !== 'computedSum') {
      control.controlPaths = [key];
      control.controlValue = value;
    }
  }
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
 * @param answers
 *   The answers from formLogic.ts, keyed by field path. Leave out to skip
 *   marking which conditional fields show.
 */
export function buildFormTree(data: FormData, locale = 'en', answers?: Map<string, unknown>): FormTree {
  const tree: FormTree = {};
  const stepFields = new Map<string, StepField[]>();

  // Walk each step in the schema and collect its fields.
  for (const step of Object.keys(data.schema.properties ?? {})) {
    stepFields.set(step, getStepFields(data, step, locale));
  }

  if (answers) {
    planConditions([...stepFields.values()].flat(), answers);
  }

  // Keep steps without fields so the tree matches the stepper.
  for (const [step, fields] of stepFields) {
    tree[step] = {};

    // Keep the sections in the order the form shows them.
    for (const section of Object.keys(data.schema.definitions[step]?.properties ?? {})) {
      if (fields.some((field) => field.section === section)) {
        tree[step][section] = {};
      }
    }

    // Place each field into the tree under its section.
    for (const field of fields) {
      tree[step][field.section] ??= {};
      tree[step][field.section][field.fieldPath.slice(2).join('_')] = field;
    }
  }

  return tree;
}
