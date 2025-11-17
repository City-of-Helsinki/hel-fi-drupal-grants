// biome-ignore-all lint/correctness/useHookAtTopLevel: @todo UHF-12501
// biome-ignore-all lint/correctness/useExhaustiveDependencies: @todo UHF-12501
// biome-ignore-all lint/suspicious/useIterableCallbackReturn: @todo UHF-12501
// biome-ignore-all lint/style/noNonNullAssertion: @todo UHF-12501
// biome-ignore-all lint/suspicious/noExplicitAny: @todo UHF-12501
import type { FieldProps, UiSchema } from '@rjsf/utils';
import { Checkbox, FileInput as HDSFileInput, TextInput } from 'hds-react';
import { useAtomValue } from 'jotai';
import { useCallback } from 'react';
import { useTranslation } from 'react-i18next';

import {
  formConfigAtom,
  getApplicationNumberAtom,
  shouldRenderPreviewAtom,
} from '../store';
import { formatErrors } from '../utils';
import { defaultCheckboxStyle } from '@/react/common/constants/checkboxStyle';

type ATVFile = {
  fileDescription?: string;
  fileId: number;
  fileName: string;
  fileType: string;
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
  label,
  multiple,
  name,
  onChange,
  rawErrors,
  readonly,
  required,
  uiSchema,
}: FieldProps) => {
  const { t } = useTranslation();
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

  const handleChange = useCallback(
    async (files: File[], existingData: any) => {
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

      const fileDescription = existingData?.fileDescription || '';

      onChange({
        integrationID,
        fileDescription,
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
      onChange={(files: File[]) => {
        handleChange(files, formData);
      }}
      required={required}
      className='hdbt-form--fileinput'
    />
  );

  const descriptionElement = (
    <TextInput
      id={`${name}-description`}
      label={t('file_description.title')}
      onChange={(e) => {
        onChange({ ...formData, fileDescription: e.target.value });
      }}
      value={formData?.fileDescription || ''}
    />
  );

  if (uiSchema?.['misc:variant'] === 'simple') {
    return (
      <div className='hdbt-form--fileinput'>
        {inputElement}
        {descriptionElement}
      </div>
    );
  }

  return (
    <div className='hdbt-form--fileinput'>
      {inputElement}
      <Checkbox
        checked={isDeliveredLater || false}
        disabled={Boolean(defaultValue.length)}
        id={`${name}-delivered-later`}
        label={Drupal.t(
          'Attachment will be delivered at later time',
          {},
          { context: 'grants_attachments' },
        )}
        onChange={(e) => {
          onChange({ ...formData, isDeliveredLater: e.target.checked });
        }}
        className='hdbt-form--checkbox'
        style={defaultCheckboxStyle}
      />
      <Checkbox
        checked={isIncludedInOtherFile || false}
        disabled={Boolean(defaultValue.length)}
        id={`${name}-included-in-other-file`}
        label={Drupal.t(
          'Attachment already delivered',
          {},
          { context: 'grants_attachments' },
        )}
        onChange={(e) => {
          onChange({ ...formData, isIncludedInOtherFile: e.target.checked });
        }}
        className='hdbt-form--checkbox'
        style={defaultCheckboxStyle}
      />
    </div>
  );
};
