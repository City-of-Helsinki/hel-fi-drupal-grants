import { FieldProps, UiSchema } from '@rjsf/utils';
import { Notification } from 'hds-react';
import { useAtomValue } from 'jotai';
import { JSONSchema7Definition } from 'json-schema';

import { shouldRenderPreviewAtom } from '../../store';

export const TextParagraph = ({ schema, uiSchema }: FieldProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const { title, items } = schema;
  const { 'misc:variant': variant } = uiSchema as UiSchema & {
    'misc:variant'?: string;
  };

  // Do not render in preview
  if (shouldRenderPreview) {
    return null;
  }

  const getTitle = (p: JSONSchema7Definition) => {
    if (p === false) {
      return '';
    }

    if (typeof p === 'object') {
      return p?.title;
    }

    return p;
  };

  return variant === 'infobox' ? (
    <Notification label={title}>
      {Array.isArray(items) && items?.length && items.map((p, index: number) => <p key={index}>{getTitle(p)}</p>)}
    </Notification>
  ) : (
    <div>
      {title && <h4>{title}</h4>}
      {Array.isArray(items) && items?.length && items.map((p, index: number) => <p key={index}>{getTitle(p)}</p>)}
    </div>
  )
}
