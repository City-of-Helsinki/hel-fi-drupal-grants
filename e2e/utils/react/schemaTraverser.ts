import type { FormPreviewResponse } from './schemaFetcher';

/**
 * Comment. Explain.
 */
export type FieldDescriptor = {
  path: string[];
  domId: string;
  type: string;
  widget: string;
  enum?: unknown[];
  required: boolean;
  minLength?: number;
  maxLength?: number;
  minimum?: number;
  maximum?: number;
  pattern?: string;
  format?: string;
  conditional: boolean;
  conditionPath?: string;
  conditionValue?: unknown;
  addText?: string;
  itemFields?: FieldDescriptor[];
};

const SKIP_WIDGETS = new Set([
  'hidden',
  'subventionTable',
  'subventionSum',
  'textParagraph',
  'atvFile',
]);

export function traverseSchema(
  schema: FormPreviewResponse['schema'],
  uiSchema: FormPreviewResponse['ui_schema'],
  step: string,
): FieldDescriptor[] {
  const results: FieldDescriptor[] = [];
  const defs = schema.definitions as Record<string, any>;

    function resolve(s: any): any {
      if (!s) return s;
      if (s.$ref) {
        const name = (s.$ref as string).replace('#/definitions/', '');
        return resolve(defs[name]);
      }
      return s;
    }

  function deriveWidget(s: any, u: any): string {
    const uiField: string | undefined = u?.['ui:field'];
    const uiWidget: string | undefined = u?.['ui:widget'];
    if (uiField) return uiField;
    if (uiWidget) return uiWidget;
    if (s.type === 'array') return 'array';
    if (s.type === 'boolean') return 'radio';
    if (s.type === 'integer' || s.type === 'number') return 'number';
    if (s.enum) return 'select';
    return 'text';
  }

  function walkItemSchema(
    itemsSchema: any,
    itemsUi: any,
    parentPath: string[],
  ): FieldDescriptor[] {
    const resolved = resolve(itemsSchema);
    if (!resolved?.properties) return [];
    const itemFields: FieldDescriptor[] = [];
    const reqList = new Set<string>(resolved.required ?? []);
    for (const [k, childSchema] of Object.entries<any>(resolved.properties)) {
      const childResolved = resolve(childSchema);
      const childUi = itemsUi?.[k] ?? {};
      const widget = deriveWidget(childResolved, childUi);
      if (SKIP_WIDGETS.has(widget)) continue;
      const childPath = [...parentPath, '0', k];
      itemFields.push({
        path: childPath,
        domId: 'root_' + childPath.join('_'),
        type: childResolved.type ?? 'string',
        widget,
        enum: childResolved.enum,
        required: reqList.has(k),
        conditional: false,
        minLength: childResolved.minLength,
        maxLength: childResolved.maxLength ?? childUi['misc:max-length'],
        minimum: childResolved.minimum,
        maximum: childResolved.maximum,
        format: childResolved.format,
        pattern: childResolved.pattern,
      });
    }
    return itemFields;
  }

  function walk(
    rawSchema: any,
    ui: any,
    path: string[],
    parentRequired: Set<string>,
    conditional: boolean,
    conditionPath?: string,
    conditionValue?: unknown,
  ) {
    const s = resolve(rawSchema);
    if (!s) return;

    const uiField: string | undefined = ui?.['ui:field'];
    const uiWidget: string | undefined = ui?.['ui:widget'];
    const widget = deriveWidget(s, ui);

    // Leaf: non-object scalar, or object with an explicit widget/field override
    const isObject = (s.type === 'object' || s.properties) && !uiField && !uiWidget;

    if (!isObject) {
      if (path.length <= 1) return; // skip the step root itself
      if (SKIP_WIDGETS.has(widget)) return;

      const fieldName = path[path.length - 1];
      const descriptor: FieldDescriptor = {
        path,
        domId: 'root_' + path.join('_'),
        type: s.type ?? 'string',
        widget,
        enum: s.enum,
        required: parentRequired.has(fieldName),
        conditional,
        conditionPath,
        conditionValue,
        minLength: s.minLength,
        maxLength: s.maxLength ?? ui?.['misc:max-length'],
        minimum: s.minimum,
        maximum: s.maximum,
        format: s.format,
        pattern: s.pattern,
      };

      if (widget === 'array') {
        descriptor.addText = ui?.['ui:options']?.addText;
        const itemsResolved = resolve(s.items);
        const itemsUi = ui?.items ?? {};
        descriptor.itemFields = walkItemSchema(itemsResolved, itemsUi, path);
      }

      results.push(descriptor);
      return;
    }

    // Object: recurse into properties
    const reqList = new Set<string>(s.required ?? []);
    for (const [k, childSchema] of Object.entries<any>(s.properties ?? {})) {
      const childUi = ui?.[k] ?? {};
      walk(childSchema, childUi, [...path, k], reqList, conditional, conditionPath, conditionValue);
    }

    // allOf if/then blocks for conditional fields
    for (const allOfEntry of (s.allOf ?? [])) {
      if (!allOfEntry.if || !allOfEntry.then) continue;
      const ifProps = allOfEntry.if.properties ?? {};
      const [condKey, condFieldSchema] = Object.entries<any>(ifProps)[0] ?? [];
      if (!condKey) continue;
      const condDomId = 'root_' + [...path, condKey].join('_');
      const condVal = condFieldSchema?.const ?? condFieldSchema?.enum?.[0];
      const thenReq = new Set<string>(allOfEntry.then.required ?? []);
      for (const [k, childSchema] of Object.entries<any>(allOfEntry.then.properties ?? {})) {
        const childUi = ui?.[k] ?? {};
        walk(childSchema, childUi, [...path, k], thenReq, true, condDomId, condVal);
      }
    }
  }

  const stepSchema = (schema.properties as any)[step];
  const stepUi = (uiSchema as any)[step] ?? {};
  walk(stepSchema, stepUi, [step], new Set(), false);
  return results;
}
