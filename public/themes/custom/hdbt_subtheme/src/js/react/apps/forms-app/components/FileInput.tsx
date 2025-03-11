import React, { useCallback } from "react";
import { WidgetProps } from "@rjsf/utils";
import { FileInput as HDSFileInput } from "hds-react";
import { formatErrors } from "./Input";

function addNameToDataURL(name: string, dataURL: string|null) {
  if (dataURL === null) {
    return null;
  }

  return dataURL.replace(';base64', `;name=${encodeURIComponent(name)};base64`);
}

function getFileDataURL(file: File): Promise<string|null> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = (event) => {
      if (typeof event.target?.result === 'string') {
        resolve(addNameToDataURL(file.name, event.target.result))
      }
      else {
        resolve(null)
      }
    }
    reader.onerror = reject
    reader.readAsDataURL(file);
  });
}

function dataUrlsToFiles(files?: string|string[]): any[] {
  if (!files) {
    return []
  }

  if (!Array.isArray(files)) {
    files = [files]
  }

  return files.map(file => {
    // These splits rely on the fact that file content is
    // encoded so that these characters only separate different fields.
    const [, content] = file.split(':')
    const [meta, data] = content.split(',')
    const [contentType, name, encoding] = meta.split(';')
    const [, fileName] = name.split('=')


    return new File([atob(data)], decodeURIComponent(fileName), {
      type: contentType ?? 'application/octet-stream'
    })
  })
}

function validateFile(applicationId: string, file: File): boolean {
  return false;
}

export const FileInput = ({
  id, label,
  onChange,
  rawErrors,
  value,
  multiple,
  required,
  accept,
}: WidgetProps) => {
  const handleChange = useCallback(async (files: File[]) => {
    // Convert to rjfs dataUrl text.
    await Promise.all(files.map(getFileDataURL))
      .then(results => {
        onChange(multiple ? results : results[0])
      })
  }, [multiple, onChange])

  return <HDSFileInput
    id={id}
    label={label}
    errorText={formatErrors(rawErrors)}
    hideLabel={false}
    invalid={Boolean(rawErrors?.length)}
    onChange={handleChange}
    defaultValue={dataUrlsToFiles(value)}
    required={required}
    accept={accept}
  />
};
