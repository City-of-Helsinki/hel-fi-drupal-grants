// biome-ignore-all lint/suspicious/noExplicitAny: This file has many references to form data that is always any

import type {
  ArrayFieldTemplateProps,
  FieldTemplateProps,
  IconButtonProps,
  ObjectFieldTemplatePropertyType,
  ObjectFieldTemplateProps,
} from '@rjsf/utils';
import { getDefaultRegistry } from '@rjsf/core';
import { Accordion, Button, Fieldset, Notification, IconCross, IconPlus } from 'hds-react';
import { createContext, type ReactNode } from 'react';
import { useAtomValue } from 'jotai';

import { formStepsAtom, getCurrentStepAtom, isEmptyPreviewAtom, shouldRenderPreviewAtom } from '../store';
import { ApplicantInfo } from './ApplicantInfo';
import { secondaryButtonTheme } from '@/react/common/constants/buttonTheme';
import { getTooltip } from '../utils';
import type { UiSchema } from '../types/UiSchema';

/**
 * Context that signals child widgets they are inside a fieldset with validation errors.
 * When true, required empty fields inside the fieldset should show their own error state.
 */
export const FieldErrorContext = createContext(false);

export const ArrayFieldTemplate = ({
  canAdd,
  items,
  onAddClick,
  registry,
  schema,
  uiSchema,
}: ArrayFieldTemplateProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const { description } = schema;
  const { ArrayFieldItemTemplate } = registry.templates;
  const isEmptyPreview = useAtomValue(isEmptyPreviewAtom);

  if (shouldRenderPreview) {
    const hideName = uiSchema?.['ui:options']?.hideNameFromPrint;
    const printableName = uiSchema?.['ui:options']?.printableName;

    // Depending on user actions, items can be empty
    const renderableItems = items
      .filter((item) => {
        const value = item?.children?.props?.formData;
        return value && Object.keys(value).length;
      })
      // biome-ignore lint/correctness/useJsxKeyInIterable: Item contains key already
      .map((item) => <ArrayFieldItemTemplate {...{ ...item, canAdd: false, hasRemove: false, hasToolbar: false }} />);

    return (
      <>
        {!hideName &&
          (printableName || schema.title) &&
          (printableName ? (
            <span className='grants-form--preview-section__label'>{printableName}</span>
          ) : (
            <span className='grants-form--preview-section__label'>{schema.title}</span>
          ))}
        {renderableItems.length ? renderableItems : '-'}
      </>
    );
  }

  const addText = uiSchema?.['ui:options']?.addText;

  return (
    <div>
      {description && <div className='hdbt-form--description'>{description}</div>}
      {items.map((item) => (
        // biome-ignore lint/correctness/useJsxKeyInIterable: Item contains key already
        <ArrayFieldItemTemplate {...item} />
      ))}
      {canAdd && !isEmptyPreview && (
        <Button onClick={onAddClick} theme={secondaryButtonTheme} type='button' iconStart={<IconPlus />}>
          {addText ? (addText as ReactNode & string) : Drupal.t('Add')}
        </Button>
      )}
    </div>
  );
};

const PreviewStep = ({
  title,
  properties,
  uiSchema,
  stepNumber,
}: {
  title?: string;
  properties: ObjectFieldTemplatePropertyType[];
  uiSchema: UiSchema;
  stepNumber?: number;
}) => {
  const printableName = uiSchema?.['ui:options']?.printableName;
  const headingText = printableName || title?.toString();
  const heading = stepNumber !== undefined ? `${stepNumber}. ${headingText}` : headingText;

  if (drupalSettings.grants_react_form.use_print) {
    return (
      <div className='hdbt-react-form__preview-accordion-item hdbt-react-form__preview-accordion-item--print'>
        <h3>{heading}</h3>
        {properties.map((field) => field.content)}
      </div>
    );
  }

  return (
    <Accordion
      className={
        'hdbt-react-form__preview-accordion-item' +
        (stepNumber === 1 ? ' hdbt-react-form__preview-accordion-item--first' : '')
      }
      heading={heading?.toString()}
      headingLevel={3}
      initiallyOpen
      language={drupalSettings.path.currentLanguage || 'fi'}
      theme={{
        '--border-color': ' var(--color-black-20)',
      }}
    >
      {properties.map((field) => field.content)}
    </Accordion>
  );
};

const PreviewSection = ({
  title,
  properties,
  uiSchema,
}: {
  title?: string;
  properties: ObjectFieldTemplatePropertyType[];
  uiSchema: UiSchema;
}) => {
  const printableName = uiSchema?.['ui:options']?.printableName;

  const visibleProperties = properties.filter((p) => !p.hidden && p.content.props?.schema?.type !== 'null');

  if (!visibleProperties.length) {
    return null;
  }

  return (
    <section className='hdbt-form--section grants-form--preview-section'>
      <h4 className='hdbt-form--section__title'>{printableName || title}</h4>
      <div className='hdbt-form--section__content'>{visibleProperties.map((field) => field.content)}</div>
    </section>
  );
};

export const ObjectFieldTemplate = ({
  errorSchema,
  idSchema,
  properties,
  schema,
  uiSchema,
}: ObjectFieldTemplateProps) => {
  const { _isSection, _step, description, title } = schema;
  const steps = useAtomValue(formStepsAtom);
  const isEmptyPreview = useAtomValue(isEmptyPreviewAtom);
  const [stepIndex, { id: stepId, label: stepLabel }] = useAtomValue(getCurrentStepAtom);
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  // Detect sections by _isSection flag (base properties) or by title
  // convention (for sections added via allOf/then conditions).
  const isSection = _isSection || (title?.endsWith('_section.title') && !_step);

  if (idSchema.$id === 'root') {
    const className = shouldRenderPreview ? 'hdbt-form__preview form-wrapper' : 'form-wrapper';
    return <div className={className}>{properties.map((field) => field.content)}</div>;
  }

  if (_step && shouldRenderPreview) {
    const stepEntry = steps && [...steps.entries()].find(([, s]) => s.id === _step);
    const stepNumber = stepEntry ? stepEntry[0] + 1 : undefined;
    return <PreviewStep title={title} properties={properties} uiSchema={uiSchema || {}} stepNumber={stepNumber} />;
  }

  if (_step && _step !== stepId && !isEmptyPreview) {
    return null;
  }

  if ((_step && _step === stepId) || (isEmptyPreview && !isSection)) {
    return (
      <>
        {title && <h2 className='grants-form__page-title'>{title}</h2>}
        <div className='grants-form__notification-container'>
          {stepIndex === 0 && !isEmptyPreview && (
            <Notification
              className='hdbt-form--notification'
              label={Drupal.t('Some information fetched from personal information')}
            >
              {Drupal.t(
                'Check the information on the form before sending the application. You can change your own information from personal information section of the site.',
              )}
            </Notification>
          )}
          {steps && stepIndex < steps.size - 2 && !isEmptyPreview && (
            <Notification
              className='hdbt-form--notification'
              label={Drupal.t('Fill in the fields to all the questions that you can answer.')}
            >
              {Drupal.t(
                'Fields marked with * are mandatory information that you must fill in in order to save and send the information.',
              )}
            </Notification>
          )}
        </div>
        {stepId === 'applicant_info' && !isEmptyPreview && (
          <section className='prh-content-block'>
            <h3 className='prh-content-block__title'>{stepLabel}</h3>
            <p>
              {Drupal.t(
                'The indicated information has been retrieved from the register of the Finnish Patent and Registration Office (PRH), and changing the information is only possible in the online service in question.',
              )}
            </p>
            <ApplicantInfo />
          </section>
        )}
        <div className='hdbt-form--page'>
          {description && <div>{description}</div>}
          {properties.map((field) => field.content)}
        </div>
      </>
    );
  }

  if (isSection && shouldRenderPreview) {
    return <PreviewSection title={title} properties={properties} uiSchema={uiSchema || {}} />;
  }

  if (isSection) {
    // Hide sections whose condition is not met — base stubs have no title;
    // the title only comes from the then-block schema when the condition matches.
    if (!title) {
      return null;
    }

    return (
      <section className='hdbt-form--section'>
        <h3 className='hdbt-form--section__title'>{title}</h3>
        <div className='hdbt-form--section__content'>
          {description && <div className='hdbt-form--description'>{description}</div>}
          {properties.map((field) => field.content)}
        </div>
      </section>
    );
  }

  if (shouldRenderPreview) {
    const hideName = uiSchema?.['ui:options']?.hideNameFromPrint;
    const printableName = uiSchema?.['ui:options']?.printableName;

    return (
      <>
        {/* @todo fix when rebuilding styles  */}
        {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
        {!hideName &&
          (printableName || title) &&
          (printableName ? (
            <span className='grants-form--preview-section__label'>{printableName}</span>
          ) : (
            <span className='grants-form--preview-section__label'>{title}</span>
          ))}
        {properties
          .filter((p) => !p.hidden)
          .map((field) => {
            if (field.content.props.uiSchema?.['ui:help']) {
              field.content.props.uiSchema['ui:help'] = '';
            }

            return field.content;
          })}
      </>
    );
  }

  const hasRequiredChild = Object.values(uiSchema || {}).some(
    (child) => typeof child === 'object' && child !== null && (child as any)['misc:required'],
  );

  return (
    <FieldErrorContext.Provider value={Boolean(errorSchema?.__errors?.length)}>
      <Fieldset
        heading={hasRequiredChild ? `${title} *` : title || ''}
        className='hdbt-form--fieldset hdbt-form--fieldset--border'
        style={{ marginInline: '0' }}
        tooltip={getTooltip(uiSchema)}
      >
        {description && <div className='hdbt-form--description'>{description}</div>}
        {properties.map((field) => field.content)}
      </Fieldset>
    </FieldErrorContext.Provider>
  );
};

const {
  templates: { FieldTemplate: DefaultFieldTemplate },
} = getDefaultRegistry();

export const FieldTemplate = (props: FieldTemplateProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const { schema } = props;

  // Don't render wrapping divs around preview items.
  if (shouldRenderPreview && (schema as any)._step) {
    return <>{props.children}</>;
  }

  // Don't render empty sections in preview.
  if (shouldRenderPreview && (schema as any)._isSection) {
    const allNull = Object.values((schema.properties || {}) as Record<string, any>).every((p) => p.type === 'null');
    if (allNull) return null;
  }

  return <DefaultFieldTemplate {...props} />;
};

export const ButtonTemplate = ({ icon, children, registry, uiSchema, ...props }: IconButtonProps) => (
  <Button
    {...props}
    style={{ marginRight: 'auto', marginTop: 'var(--spacing-m)' }}
    theme={secondaryButtonTheme}
    type='button'
    iconStart={<IconCross />}
  >
    {children as ReactNode & string}
  </Button>
);

export const RemoveButtonTemplate = (props: IconButtonProps) => {
  const { uiSchema } = props;
  const removeText = uiSchema?.['ui:options']?.removeText;
  return <ButtonTemplate {...props}>{removeText || Drupal.t('Remove')}</ButtonTemplate>;
};
