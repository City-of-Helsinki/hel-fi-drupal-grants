// biome-ignore-all lint/correctness/noUnusedFunctionParameters: @todo UHF-12501
// biome-ignore-all lint/style/noNonNullAssertion: @todo UHF-12501
import { useCallback } from 'react';
import type { FieldProps, UiSchema } from '@rjsf/utils';
import { Checkbox, FileInput as HDSFileInput } from 'hds-react';
import { useAtomValue } from 'jotai';
import {
  formConfigAtom,
  getApplicationNumberAtom,
  shouldRenderPreviewAtom,
} from '../store';
import { formatErrors } from '../utils';

type ATVFile = {
  fileType: string;
  fileName: string;
  fileId: number;
  href: string;
  size: number;
};

async function uploadFiles(
  field: string,
  applicationNumber: string,
  token: string,
  files: File[],
  fileType: number,
): Promise<ATVFile | null> {
  if (!files.length) {
    return null;
  }

  const formData = new FormData();

  formData.append('fieldName', field);
  // biome-ignore lint/suspicious/useIterableCallbackReturn: @todo UHF-12501
  files.forEach((file) => formData.append('file', file));

  const response = await fetch(`/en/application/${applicationNumber}/upload`, {
    method: 'POST',
    body: formData,
    headers: { 'X-CSRF-Token': token },
  });

  if (!response.ok) {
    throw new Error('Failed to upload file');
  }

  return { ...(await response.json()), fileType };
}

const filesFromATVData = (value?: ATVFile): File[] => {
  if (!value || !value.fileName) {
    return [];
  }

  const data = new Uint8Array(value.size);
  return [new File([data], value.fileName)];
};

export const FileInput = ({
  accept,
  formData,
  id,
  idSchema,
  label,
  multiple,
  name,
  onChange,
  rawErrors,
  readonly,
  required,
  uiSchema,
}: FieldProps) => {
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const applicationNumber = useAtomValue(getApplicationNumberAtom);
  const { token } = useAtomValue(formConfigAtom)!;
  const { 'misc:file-type': fileType } = uiSchema as UiSchema & {
    'misc:file-type': number;
  };
  const defaultValue = filesFromATVData(formData);
  const { isDeliveredLater, isIncludedInOtherFile } = formData || {};

  if (shouldRenderPreview) {
    return (
      <>
        {defaultValue.map((file) => (
          <p key={file.name}>{file.name}</p>
        ))}
      </>
    );
  }

  // biome-ignore lint/correctness/useExhaustiveDependencies: @todo UHF-12501
  // biome-ignore lint/correctness/useHookAtTopLevel: @todo UHF-12501
  const handleChange = useCallback(
    async (files: File[]) => {
      if (!files.length) {
        onChange(undefined);
        return;
      }

      const result = await uploadFiles(
        name,
        applicationNumber,
        token,
        files,
        fileType,
      );

      if (!result) {
        return;
      }

      const { href: integrationID, ...rest } = result;

      onChange({
        integrationID,
        isDeliveredLater: false,
        isIncludedInOtherFile: false,
        isNewAttachment: true,
        ...rest,
      });
    },
    [applicationNumber, multiple, onChange, token],
  );

  const inputElement = (
    <HDSFileInput
      accept={accept}
      defaultValue={defaultValue}
      disabled={readonly}
      dragAndDrop
      errorText={formatErrors(rawErrors)}
      hideLabel={false}
      id={id || ''}
      invalid={Boolean(rawErrors?.length)}
      label={label}
      language={drupalSettings.path.currentLanguage}
      // 20mb in bytes
      maxSize={20 * 1024 * 1024}
      onChange={handleChange}
      required={required}
    />
  );

  if (uiSchema['misc:variant'] === 'simple') {
    inputElement.className = 'hdbt-form--fileinput';
    return inputElement;
  }

  return (
    <div className='hdbt-form--fileinput'>
      {inputElement}
      <Checkbox
        checked={isDeliveredLater || false}
        disabled={defaultValue.length}
        id={`${name}-delivered-later`}
        label={Drupal.t(
          'Attachment will be delivered at later time',
          {},
          { context: 'grants_attachments' },
        )}
        onChange={(e) => {
          onChange({ ...formData, isDeliveredLater: e.target.checked });
        }}
      />
      <Checkbox
        checked={isIncludedInOtherFile || false}
        disabled={defaultValue.length}
        id={`${name}-included-in-other-file`}
        label={Drupal.t(
          'Attachment already delivered',
          {},
          { context: 'grants_attachments' },
        )}
        onChange={(e) => {
          onChange({ ...formData, isIncludedInOtherFile: e.target.checked });
        }}
      />
    </div>
  );
};
