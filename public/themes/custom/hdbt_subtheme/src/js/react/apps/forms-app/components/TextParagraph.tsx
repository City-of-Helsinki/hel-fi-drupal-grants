import { FieldProps, UiSchema } from '@rjsf/utils';
import { Notification } from 'hds-react';

export const TextParagraph = ({ schema, uiSchema }: FieldProps) => {
  const { title, items } = schema;
  const { 'misc:variant': variant } = uiSchema as UiSchema & {
    'misc:variant'?: string;
  };

  return variant === 'infobox' ? (
    <Notification label={title}>
      {Array.isArray(items) && items?.length && items.map((p, index: number) => <p key={index}>{p}</p>)}
    </Notification>
  ) : (
    <div>
      {title && <h4>{title}</h4>}
      {Array.isArray(items) && items?.length && items.map((p, index: number) => <p key={index}>{p}</p>)}
    </div>
  )
}
