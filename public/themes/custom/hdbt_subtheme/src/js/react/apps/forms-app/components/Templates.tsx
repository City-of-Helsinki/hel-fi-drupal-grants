import { ArrayFieldTemplateProps, IconButtonProps, ObjectFieldTemplateProps } from '@rjsf/utils'
import { Button, ButtonPresetTheme, ButtonVariant, Fieldset } from 'hds-react';
import { useAtomValue } from 'jotai';
import { getCurrentStepAtom } from '../store';
import { ApplicantInfo } from './ApplicantInfo';
import { Children, ReactNode } from 'react';

export const ArrayFieldTemplate = ({
  canAdd,
  items,
  onAddClick,
  registry,
  schema,
  uiSchema,
}: ArrayFieldTemplateProps) => {
  const { description, title } = schema;
  const { ArrayFieldItemTemplate } = registry.templates;

  const addText = uiSchema && uiSchema['ui:options'] && uiSchema['ui:options'].addText || null; // @ts-ignore{uiSchema: {'ui:options': {}}} = props;

  return (
    <div>
        <div className='webform-section-wrapper'>
          {description &&
            <div className='form-item form-item-prh-markup'>
              {description}
            </div>
          }
        </div>
      {items.map((item) => <ArrayFieldItemTemplate {...item} />)}
      {canAdd &&
        <Button
          onClick={onAddClick}
          theme={ButtonPresetTheme.Black}
          style={{
            marginTop: 'var(--spacing-m)'
          }}
          type='button'
          variant={ButtonVariant.Primary}
        >
          {addText ? addText as ReactNode & string : Drupal.t('Add')}
        </Button>
      }
    </div>
  )
};

export const ObjectFieldTemplate = ({
  idSchema,
  properties,
  schema,
  uiSchema,
}: ObjectFieldTemplateProps) => {
  const { additionalProperties, description, _isSection, title, _step } = schema;
  const { id: stepId } = useAtomValue(getCurrentStepAtom)[1];

  if (idSchema.$id === 'root') {
    return (
      <div className='form-wrapper'>
        {properties.map((field) => field.content)}
      </div>
    )
  }
  // @todo fix type errors with additionalProperties
  if (_step && _step !== stepId) {
    return null;
  }

  if (_step && _step === stepId) {
    return (
      <>
        {title && <h2 className='grants__page-header'>{title}</h2>}
        {
          stepId === 'applicant_info' &&
          <section className='grants-profile--imported-section webform-section'>
            <div className='webform-section-flex-wrapper'>
              <div className='form-item-prh-markup'>
                <div className='grants-profile-prh-info'>
                  {Drupal.t('The indicated information has been retrieved from the register of the Finnish Patent and Registration Office (PRH), and changing the information is only possible in the online service in question.')}
                </div>
              </div>
              <ApplicantInfo />
            </div>
          </section>
        }
        <div className='webform-section-wrapper'>
          {description &&
            <div className='form-item form-item-prh-markup'>
              {description}
            </div>
          }
        </div>
        {properties.map(field => field.content)}
      </>
    )
  }

  if (_isSection) {
    return (
      <section className='form-item webform-section'>
        <div className='webform-section-flex-wrapper'>
          <h3 className='webform-section-title'>
            {title}
          </h3>
          <div className='webform-section-wrapper'>
            {description && <div className='form-item'>{description}</div>}
            {properties.map((field) => field.content)}
          </div>
        </div>
      </section>
    );
  }

  return (
    <Fieldset
      heading={title || ''}
      border
    >
      {description && <div className='form-item'>{description}</div>}
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
}: IconButtonProps) => {
  return (
    <Button
      {...props}
      style={{
        display: 'inline-block',
        marginRight: 'auto',
        marginTop: 'var(--spacing-m)',
      }}
      theme={ButtonPresetTheme.Black}
      type='button'
      variant={ButtonVariant.Primary}
    >
      {children as ReactNode & string}
    </Button>
  )
}

export const AddButtonTemplate = (props: IconButtonProps) => {
  const addText = props.uiSchema && props.uiSchema['ui:options'] && props.uiSchema['ui:options'].addText || null; // @ts-ignore{uiSchema: {'ui:options': {}}} = props;
  return (
    <ButtonTemplate
      {...props}
    >
      {addText ? addText : Drupal.t('Add')}
    </ButtonTemplate>
  )
};

export const RemoveButtonTemplate = (props: IconButtonProps) => {
  const removeText = props.uiSchema && props.uiSchema['ui:options'] && props.uiSchema['ui:options'].removeText || null; // @ts-ignore{uiSchema: {'ui:options': {}}} = props;
  return (
    <ButtonTemplate
      {...props}
    >
      {removeText ? removeText : Drupal.t('Remove')}
    </ButtonTemplate>
  )
};
