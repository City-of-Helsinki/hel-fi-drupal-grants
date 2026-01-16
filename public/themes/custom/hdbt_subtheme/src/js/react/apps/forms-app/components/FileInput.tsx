// biome-ignore-all lint/correctness/useHookAtTopLevel: @todo UHF-12501
// biome-ignore-all lint/correctness/useExhaustiveDependencies: @todo UHF-12501
// biome-ignore-all lint/suspicious/useIterableCallbackReturn: @todo UHF-12501
// biome-ignore-all lint/style/noNonNullAssertion: @todo UHF-12501
// biome-ignore-all lint/suspicious/noExplicitAny: @todo UHF-12501
import type { FieldProps, UiSchema } from '@rjsf/utils';
import { Checkbox, FileInput as HDSFileInput, TextInput } from 'hds-react';
import { useAtomValue, useSetAtom } from 'jotai';
import { useTranslation } from 'react-i18next';

import { formConfigAtom, getApplicationNumberAtom, pushNotificationAtom, shouldRenderPreviewAtom } from '../store';
import { formatErrors } from '../utils';
import { useState } from 'react';
import { defaultCheckboxStyle } from '@/react/common/constants/checkboxStyle';

type ATVFile = {
  description?: string;
  fileId: number;
  fileName: string;
  fileType: string;
  href: string;
  size: number;
};

type PersistedFile = ATVFile & {
  integrationID: string;
  isDeliveredLater?: boolean;
  isIncludedInOtherFile?: boolean;
  isNewAttachment?: boolean;
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
  name,
  onChange,
  rawErrors,
  readonly,
  required,
  uiSchema,
}: FieldProps) => {
  const [refreshKey, setRefreshKey] = useState(0);
  const { t } = useTranslation();
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const applicationNumber = useAtomValue(getApplicationNumberAtom);
  const { token } = useAtomValue(formConfigAtom)!;
  const pushNotification = useSetAtom(pushNotificationAtom);
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

  const handleResponseError = async (response: Response) => {
    const json = await response.json();

    if (!json?.error) {
      throw new Error('Failed to remove file');
    }

    pushNotification({
      children: <div>{json.error}</div>,
      label: t('file_removal_failed.title'),
      type: 'error',
    });

    // Force re-render to reset the FileInput state
    setRefreshKey((prevKey) => prevKey + 1);
  };

  const handleRemoval = async (existingData: PersistedFile | undefined) => {
    if (!existingData?.integrationID) {
      onChange(undefined);
      return;
    }

    const fileUrl = new URL(existingData.integrationID);
    const pathSemgments = fileUrl.pathname.split('/').filter(Boolean);
    const attachmentId = pathSemgments.pop();
    const response = await fetch(`/application/${applicationNumber}/delete/${attachmentId}`, {
      method: 'POST',
      headers: { 'X-CSRF-Token': token },
    });

    if (!response.ok) {
      handleResponseError(response);
      return;
    }

    onChange(undefined);
  };

  const handleChange = async (files: File[], existingData: PersistedFile | undefined) => {
    if (!files.length) {
      handleRemoval(existingData);
      return;
    }

    const result = await uploadFiles(name, applicationNumber, token, files, fileType);

    if (!result) {
      return;
    }

    const { href: integrationID, ...rest } = result;

    const description = existingData?.description || '';

    onChange({
      integrationID,
      description,
      isDeliveredLater: false,
      isIncludedInOtherFile: false,
      isNewAttachment: true,
      ...rest,
    });
  };

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
        onChange({ ...formData, description: e.target.value });
      }}
      value={formData?.description || ''}
    />
  );

  if (uiSchema?.['misc:variant'] === 'simple') {
    return (
      <div className='hdbt-form--fileinput' key={refreshKey}>
        {inputElement}
        {descriptionElement}
      </div>
    );
  }

  return (
    <div className='hdbt-form--fileinput' key={refreshKey}>
      {inputElement}
      <Checkbox
        checked={isDeliveredLater || false}
        disabled={Boolean(defaultValue.length)}
        id={`${name}-delivered-later`}
        label={Drupal.t('Attachment will be delivered at later time', {}, { context: 'grants_attachments' })}
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
        label={Drupal.t('Attachment already delivered', {}, { context: 'grants_attachments' })}
        onChange={(e) => {
          onChange({ ...formData, isIncludedInOtherFile: e.target.checked });
        }}
        className='hdbt-form--checkbox'
        style={defaultCheckboxStyle}
      />
    </div>
  );
};
