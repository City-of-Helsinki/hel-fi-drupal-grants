import { RJSFSchema } from '@rjsf/utils';
import { Preview } from './Preview';

export const SubmittedForm = ({
  formData,
  schema,
}: {
  formData: any,
  schema: RJSFSchema
}) => {
  console.log(formData);

  return (
    <>
      <h1>{schema.title}</h1>
      <Preview
        formData={formData}
        schema={schema}
      />
    </>
  );
}
