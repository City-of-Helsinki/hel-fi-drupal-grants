// biome-ignore-all lint/suspicious/noExplicitAny: This file has many references to form data that is always any

import type {
  ArrayFieldTemplateProps,
  FieldTemplateProps,
  IconButtonProps,
  ObjectFieldTemplatePropertyType,
  ObjectFieldTemplateProps,
} from '@rjsf/utils';
import { getDefaultRegistry } from '@rjsf/core';
import { Accordion, Button, Fieldset, Notification, IconCross, IconPlus, type AccordionTheme } from 'hds-react';
import type { ReactNode } from 'react';
import { useAtomValue } from 'jotai';

import {
  formStepsAtom,
  getApplicantTypeAtom,
  getCurrentStepAtom,
  isEmptyPreviewAtom,
  shouldRenderPreviewAtom,
} from '../store';
import { ApplicantInfo, PreviewApplicantInfo } from './ApplicantInfo';
import { secondaryButtonTheme } from '@/react/common/constants/buttonTheme';
import { getTooltip } from '../utils';
import type { UiSchema } from '../types/UiSchema';

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
  stepId,
}: {
  title?: string;
  properties: ObjectFieldTemplatePropertyType[];
  uiSchema: UiSchema;
  stepNumber?: number;
  stepId?: string;
}) => {
  const printableName = uiSchema?.['ui:options']?.printableName;
  const headingText = printableName || title?.toString();
  const heading = stepNumber !== undefined ? `${stepNumber}. ${headingText}` : headingText;

  if (drupalSettings.grants_react_form.use_print) {
    return (
      <div className='hdbt-react-form__preview-accordion-item hdbt-react-form__preview-accordion-item--print'>
        <h3>{heading}</h3>
        {stepId === 'applicant_info' && <PreviewApplicantInfo />}
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
      theme={
        {
          '--border-color': ' var(--color-black-20)',
          '--color-hover': 'var(--header-color)',
          '--color-focus': 'var(--header-color)',
          '--header-outline-color-focus': 'var(--header-color)',
        } as AccordionTheme
      }
    >
      {stepId === 'applicant_info' && <PreviewApplicantInfo />}
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

export const ObjectFieldTemplate = ({ idSchema, properties, schema, uiSchema }: ObjectFieldTemplateProps) => {
  const { _isSection, _step, description, title } = schema;
  const steps = useAtomValue(formStepsAtom);
  const isEmptyPreview = useAtomValue(isEmptyPreviewAtom);
  const [stepIndex, { id: stepId, label: stepLabel }] = useAtomValue(getCurrentStepAtom);
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const applicantType = useAtomValue(getApplicantTypeAtom);
  const prhBlockTitle =
    applicantType === 'registered_community'
      ? Drupal.t('Community for which the grant is being applied for', {}, { context: 'Grants application' })
      : Drupal.t('Applicant details', {}, { context: 'Grants application' });

  if (idSchema.$id === 'root') {
    const className = shouldRenderPreview ? 'hdbt-form__preview form-wrapper' : 'form-wrapper';
    return <div className={className}>{properties.map((field) => field.content)}</div>;
  }

  if (_step && shouldRenderPreview) {
    const stepEntry = steps && [...steps.entries()].find(([, s]) => s.id === _step);
    const stepNumber = stepEntry ? stepEntry[0] + 1 : undefined;
    return (
      <PreviewStep
        title={title}
        properties={properties}
        uiSchema={uiSchema || {}}
        stepNumber={stepNumber}
        stepId={_step}
      />
    );
  }

  if (_step && _step !== stepId && !isEmptyPreview) {
    return null;
  }

  if ((_step && _step === stepId) || (isEmptyPreview && !_isSection)) {
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
            <h3 className='prh-content-block__title'>{prhBlockTitle}</h3>
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

  if (_isSection && shouldRenderPreview) {
    return <PreviewSection title={title} properties={properties} uiSchema={uiSchema || {}} />;
  }

  if (_isSection) {
    const visibleProperties = properties.filter((p) => !p.hidden && p.content.props?.schema?.type !== 'null');
    if (!visibleProperties.length) {
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

  const visibleFieldsetProperties = properties.filter((p) => !p.hidden && p.content.props?.schema?.type !== 'null');
  if (!visibleFieldsetProperties.length) {
    return null;
  }

  return (
    <Fieldset
      heading={hasRequiredChild ? `${title} *` : title || ''}
      className='hdbt-form--fieldset hdbt-form--fieldset--border'
      style={{ marginInline: '0' }}
      tooltip={getTooltip(uiSchema)}
    >
      {description && <div className='hdbt-form--description'>{description}</div>}
      {properties.map((field) => field.content)}
    </Fieldset>
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

  // Don't render wrappers for inactive conditional sections.
  // Sections whose allOf/then condition is not met have only empty placeholder objects
  // in their base schema (e.g. project_measures_wrapper: {type:object, default:{}}),
  // which have no sub-properties and would render as nothing.
  if (!shouldRenderPreview && (schema as any)._isSection) {
    const sectionProps = Object.values((schema.properties || {}) as Record<string, any>);
    const hasVisibleContent = sectionProps.some((p: any) => {
      if (p.type === 'null') return false;
      if (p.type === 'object') {
        const sub = p.properties as Record<string, any> | undefined;
        return !!sub && Object.values(sub).some((s: any) => s?.type !== 'null');
      }
      return true; // string, integer, number, boolean, array — always renderable
    });
    if (!hasVisibleContent) return null;
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
