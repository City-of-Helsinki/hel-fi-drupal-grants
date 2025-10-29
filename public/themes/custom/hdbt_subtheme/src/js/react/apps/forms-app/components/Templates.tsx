import { ArrayFieldTemplateProps, IconButtonProps, ObjectFieldTemplatePropertyType, ObjectFieldTemplateProps } from '@rjsf/utils'
import { Accordion, Button, Fieldset, Notification, IconCross, IconPlus } from 'hds-react';
import { ReactNode } from 'react';
import { useAtomValue } from 'jotai';

import { formStepsAtom, getCurrentStepAtom, shouldRenderPreviewAtom } from '../store';
import { ApplicantInfo } from './ApplicantInfo';
import { secondaryButtonTheme } from '@/react/common/constants/buttonTheme';

export const ArrayFieldTemplate = ({
  canAdd,
  idSchema,
  items,
  onAddClick,
  registry,
  schema,
  uiSchema,
}: ArrayFieldTemplateProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const { description } = schema;
  const { ArrayFieldItemTemplate } = registry.templates;

  if (shouldRenderPreview) {
    const hideName = uiSchema?.['ui:options']?.hideNameFromPrint;
    const printableName = uiSchema?.['ui:options']?.printableName;

    // Depending on user actions, items can be empty
    const renderableItems = items.filter(item => {
      const value = item?.children?.props?.formData;
      return value && Object.keys(value).length;
    }).map(item => <ArrayFieldItemTemplate {...{
      ...item,
      canAdd: false,
      hasRemove: false,
      hasToolbar: false,
    }} />)

    return (
      <>
        {/* @todo fix when rebuilding styles  */}
        {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
        {!hideName && (printableName ? <label>{printableName}</label> : <label>{schema.title}</label>)}
        {renderableItems.length ? renderableItems : '-'}
      </>
    )
  }

  const addText = uiSchema?.['ui:options']?.addText;

  return (
    <div>
      {description &&
        <div className='hdbt-form--description'>
          {description}
        </div>
      }
      {items.map((item) => <ArrayFieldItemTemplate {...item} />)}
      {canAdd &&
        <Button
          onClick={onAddClick}
          theme={secondaryButtonTheme}
          type='button'
          iconStart={<IconPlus />}
        >
          {addText ? addText as ReactNode & string : Drupal.t('Add')}
        </Button>
      }
    </div>
  )
};

const PreviewStep = ({
  title,
  properties,
  uiSchema,
}: {
  title?: string;
  properties: ObjectFieldTemplatePropertyType[];
  uiSchema: any;
}) => {
  const printableName = uiSchema?.['ui:options']?.printableName;

  return (
    <Accordion
      heading={printableName || title?.toString()}
      headingLevel={3}
      initiallyOpen
    >
      {properties.map((field) => field.content)}
    </Accordion>
  );
}

const PreviewSection = ({
  title,
  properties,
  uiSchema,
}: {
  title?: string;
  properties: ObjectFieldTemplatePropertyType[];
  uiSchema: any;
}) => {
  const printableName = uiSchema?.['ui:options']?.printableName;

  return (
    <section className='hdbt-form--section grants-form--preview-section'>
      <h4 className='hdbt-form--section__title'>{printableName || title}</h4>
      <div className='hdbt-form--section__content'>
        {properties.map((field) => field.content)}
      </div>
    </section>
  );
};

export const ObjectFieldTemplate = ({
  idSchema,
  properties,
  schema,
  uiSchema,
}: ObjectFieldTemplateProps) => {
  const { _isSection, _step, description, title } = schema;
  const steps = useAtomValue(formStepsAtom);
  const [stepIndex, { id: stepId, label: stepLabel }] = useAtomValue(getCurrentStepAtom);
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  if (idSchema.$id === 'root') {
    return (
      <div className='form-wrapper'>
        {properties.map((field) => field.content)}
      </div>
    )
  }

  if (_step && shouldRenderPreview) {
    return <PreviewStep title={title} properties={properties} uiSchema={uiSchema} />;
  }

  if (_step && _step !== stepId) {
    return null;
  }

  if (_step && _step === stepId) {
    return (
      <>
        {title && <h2 className='grants-form__page-title'>{title}</h2>}
        <div className="grants-form__notification-container">
          {stepIndex === 0 && (
            <Notification className="hdbt-form--notification" label={Drupal.t('Some information fetched from personal information')}>
              {Drupal.t('Check the information on the form before sending the application. You can change your own information from personal information section of the site.')}
            </Notification>
          )}
          {steps && stepIndex < steps.size - 2 && (
            <Notification className="hdbt-form--notification" label={Drupal.t('Fill in the fields to all the questions that you can answer.')}>
              {Drupal.t('Fields marked with * are mandatory information that you must fill in in order to save and send the information.')}
            </Notification>
          )}
        </div>
        {
          stepId === 'applicant_info' &&
          <section className='prh-content-block'>
            <h3 className='prh-content-block__title'>{stepLabel}</h3>
            <p>
              {Drupal.t('The indicated information has been retrieved from the register of the Finnish Patent and Registration Office (PRH), and changing the information is only possible in the online service in question.')}
            </p>
          <ApplicantInfo />
          </section>
        }
        <div className='hdbt-form--page'>
          {description &&
            <div>
              {description}
            </div>
          }
          {properties.map(field => field.content)}
        </div>
      </>
    )
  }

  if (_isSection && shouldRenderPreview) {
    return <PreviewSection title={title} properties={properties} uiSchema={uiSchema} />
  }

  if (_isSection) {
    return (
      <section className='hdbt-form--section'>
        <h3 className="hdbt-form--section__title">
          {title}
        </h3>
        <div className="hdbt-form--section__content">
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
        {!hideName && printableName ? <label>{printableName}</label> : <label>{title}</label>}
        {properties.map((field) => {
          if (field.content.props.uiSchema?.['ui:help']) {
            field.content.props.uiSchema['ui:help'] = '';
          }

          return field.content;
        })}
      </>
    );
  }

  return (
    <Fieldset
      heading={title || ''}
      className='hdbt-form--fieldset'
      style={{
        marginInline: '0',
      }}
    >
      {description && <div className='hdbt-form--description'>{description}</div>}
      {properties.map((field) => field.content)}
    </Fieldset>
  );
};

export const ButtonTemplate = ({
  icon,
  children,
  registry,
  uiSchema,
  ...props
}: IconButtonProps) => (
  <Button
    {...props}
    style={{
      marginRight: 'auto',
      marginTop: 'var(--spacing-m)',
    }}
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
  return (
    <ButtonTemplate
      {...props}
    >
      {removeText || Drupal.t('Remove')}
    </ButtonTemplate>
  )
};
