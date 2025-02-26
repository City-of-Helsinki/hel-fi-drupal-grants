import { ArrayFieldTemplateProps, ObjectFieldTemplateProps } from '@rjsf/utils'
import { Fieldset } from 'hds-react';
import { useAtomValue } from 'jotai';
import { getCurrentStepAtom, getFormConfigAtom } from '../store';
import { ApplicantInfo } from './ApplicantInfo';

export const FieldsetWidget = () => (
    <Fieldset
      border
      heading=""
    >

    </Fieldset>
  );

export const ArrayFieldTemplate = ({
  idSchema,
  schema,
  uiSchema,
}: ArrayFieldTemplateProps) => null

export const ObjectFieldTemplate = ({
  idSchema,
  properties,
  schema,
  uiSchema,
}: ObjectFieldTemplateProps) => {
  const { additionalProperties, title, description } = schema;
  const { id: stepId } = useAtomValue(getCurrentStepAtom)[1];

  if (idSchema.$id === 'root') {
    return (
      <div className='form-wrapper'>
        {properties.map((field) => field.content)}
      </div>
    )
  }

  // @todo fix type errors with additionalProperties
  if (
    // @ts-expect-error
    additionalProperties?.step &&
    // @ts-expect-error
    additionalProperties.step !== stepId
  ) {
    return null;
  }

  if (
    // @ts-expect-error
    additionalProperties?.step &&
    // @ts-expect-error
    additionalProperties.step === stepId
  ) {
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

  if (uiSchema && uiSchema['ui:widget'] === 'FieldsetWidget') {
    return (
      <Fieldset
        border={properties.length > 1}
        heading={description || ''}
      >
        {properties.map((field) => field.content)}
      </Fieldset>
    );
  }

  if (!title) {
    return (
      <>
        {properties.map((field) => field.content)}
      </>
    )
  }

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
};
