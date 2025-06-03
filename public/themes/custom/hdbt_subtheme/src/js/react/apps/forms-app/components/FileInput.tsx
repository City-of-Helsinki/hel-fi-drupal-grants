import React, { useCallback } from "react";
import { FieldProps, UiSchema } from "@rjsf/utils";
import { FileInput as HDSFileInput } from "hds-react";
import { useAtomValue } from "jotai";
import { formatErrors } from "./Input";
import { formConfigAtom, getApplicationNumberAtom, shouldRenderPreviewAtom } from "../store";

type ATVFile = {
  fileType: string;
  fileName: string;
  fileId: number;
  href: string;
  size: number;
}

async function uploadFiles(field: string, applicationNumber: string, token: string, files: File[], fileType: number): Promise<ATVFile|null> {
  if (!files.length) {
    return null;
  }

  const formData = new FormData()

  formData.append('fieldName', field)
  files.forEach(file => formData.append('file', file))

  const response = await fetch(`/en/application/${applicationNumber}/upload`, {
    method: 'POST',
    body: formData,
    headers: {
      'X-CSRF-Token': token
    }
  });

  if (!response.ok) {
    throw new Error('Failed to upload file');
  }

  return {
    ...await response.json(),
    fileType,
  };
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
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);
  const applicationNumber = useAtomValue(getApplicationNumberAtom);
  const { token } = useAtomValue(formConfigAtom)!;
  const { 'misc:file-type': fileType } = uiSchema as UiSchema & {
    'misc:file-type': number;
  };
  const defaultValue = filesFromATVData(formData);

  if (shouldRenderPreview) {
    return (
      <>
        {defaultValue.map(file => <p key={file.name}>{file.name}</p>)}
      </>
    )
  }

  const handleChange = useCallback(async (files: File[]) => {
    const result = await uploadFiles(name, applicationNumber, token, files, fileType);

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
  }, [applicationNumber, multiple, onChange, token])

  return <HDSFileInput
    accept={accept}
    // @ts-ignore @fixme typescript is wrong
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
};
