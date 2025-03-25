import Form from '@rjsf/core';
import { findSchemaDefinition, RJSFSchema } from '@rjsf/utils';
import { Accordion } from 'hds-react';
import { JSONSchema7, JSONSchema7Definition, JSONSchema7Type } from 'json-schema';
import { Fragment, ReactFragment, RefObject } from 'react';

/**
 * Recursive function to generate a printable form of a given property.
 *
 * @param {objcet} property - the property definition from the schema.
 * @param {string} key - the key of the property.
 * @param {object} data - the data to be printed.
 * @return {ReactFragment|null} - Resulting element of rnull.
 */
const getPrintableProperty = (
  property: JSONSchema7Definition,
  key: string,
  data: any,
): ReactFragment|null => {
  if (typeof property !== 'object') {
    return null;
  }

  if (property.type === 'object') {
    return (
      <Fragment key={key}>
        {/* @todo fix this when updating styles. Leave for now since the old styles use label without control */}
        {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
        <label>{property.title || ''}</label>
        {property.properties && Object.entries(property.properties).map(([subKey, subProperty]) => getPrintableProperty(
          subProperty,
          subKey,
          data?.[subKey]
        ))}
      </Fragment>
    )
  }

  if (property.type === 'array') {
    return (
      <Fragment key={key}>
        {/* @todo fix this when updating styles. Leave for now since the old styles use label without control */}
        {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
        <label>{property.title || ''}</label>
        {property.items && Object.entries(property.items).map(([subKey, subProperty]) => getPrintableProperty(
          subProperty,
          subKey,
          data?.[subKey]
        ))}
      </Fragment>
    )
  }

  const printableData = data || '-';

  return  (
    <Fragment key={key}>
        {/* @todo fix this when updating styles. Leave for now since the old styles use label without control */}
        {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
      <label>{property.title || ''}</label>
      <span>{printableData}</span>
    </Fragment>
  );
}

const PreviewSection = ({
  data,
  definition
}: {
  data: any,
  definition: JSONSchema7
}) => (
  definition.properties ?
    <>
      {Object.entries(definition.properties).map(([key, value], index) => {

        if (typeof value !== 'object') {
          return null;
        }

        return (
          <section
            className='form-item webform-section form-wrapper'
            key={index}
            style={{
              width: '100%',
            }}
          >
            <div className='webform-section-flex-wrapper'>
              <h4 className='webform-section-title'>{value.title}</h4>
              <div>
                {value.properties && Object.entries(value.properties).map(([propertyKey, property]) => getPrintableProperty(
                  property,
                  propertyKey,
                  data?.[key]?.[propertyKey]
                ))}
              </div>
            </div>
          </section>
        )}
      )}
    </> : null
);

const PreviewStep = ({
  data,
  definition,
  stepIndex,
  title,
}: {
  data: any,
  definition: JSONSchema7,
  stepIndex: number,
  title: JSONSchema7Type,
}) => (
  <Accordion
    heading={`${stepIndex}. ${title?.toString()}`}
    headingLevel={3}
    initiallyOpen
  >
    <PreviewSection
      data={data}
      definition={definition}
    />
  </Accordion>
);

/**
 * Renders a preview of the form.
 *
 * @return {JSX.Element} - The preview
 */
export const Preview = ({
  formRef,
  schema,
}: {
  formRef: RefObject<Form<any, RJSFSchema, any>>,
  schema: RJSFSchema,
}) => (
  <div className='forms-app__preview'>
    <h2>{Drupal.t('Confirm, preview and submit')}</h2>
    {schema.properties && Object.entries(schema.properties).map((property, index: number) => {
      const [key, value] = property;

      // By definition, this might also be a boolean.
      if (typeof value !== 'object') {
        return null;
      }

      const { $ref } = value;

      return (
        <PreviewStep
          data={formRef.current?.state.formData?.[key]}
          definition={findSchemaDefinition($ref, schema)}
          key={key}
          stepIndex={index + 1}
          title={value.title || ''}
        />
      );
    })}
  </div>
);
