// biome-ignore-all lint/suspicious/noArrayIndexKey: @todo UHF-12501
import type { FieldProps, UiSchema } from '@rjsf/utils';
import { Notification } from 'hds-react';
import { useAtomValue } from 'jotai';
import type { JSONSchema7Definition } from 'json-schema';
import { htmlToReact } from '@/react/common/helpers/htmlToReact';
import { shouldRenderPreviewAtom } from '../../store';
import { ALLOWED_HTML_TAGS, getTooltip } from '../../utils';

export const TextParagraph = ({ schema, uiSchema }: FieldProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const { title, items } = schema;
  const { 'misc:variant': variant } = uiSchema as UiSchema & { 'misc:variant'?: string };

  // Do not render in preview
  if (shouldRenderPreview) {
    return null;
  }

  const getTitle = (p: JSONSchema7Definition): string => {
    if (typeof p === 'boolean') {
      return '';
    }

    if (typeof p === 'object') {
      return p?.title ?? '';
    }

    return p;
  };

  const tooltip = getTooltip(uiSchema);
  const paragraphs =
    Array.isArray(items) &&
    items?.length &&
    items.map((p, index) => (
      <p className='hdbt-form--paragraph__content' key={index}>
        {htmlToReact(getTitle(p) ?? '', ALLOWED_HTML_TAGS)}
      </p>
    ));

  return variant === 'infobox' ? (
    <Notification label={title} className='hdbt-form--notification'>
      {Array.isArray(items) &&
        items?.length &&
        items.map((p, index) => <p key={index}>{htmlToReact(getTitle(p) ?? '', ALLOWED_HTML_TAGS)}</p>)}
    </Notification>
  ) : (
    <div className='hdbt-form--paragraph'>
      {title && <h4 className='hdbt-form--paragraph__title'>{title}</h4>}
      {tooltip ? (
        <div className='hdbt-form--paragraph__body--with-tooltip'>
          <div>{paragraphs}</div>
          {tooltip}
        </div>
      ) : (
        paragraphs
      )}
    </div>
  );
};
