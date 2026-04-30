import { Select } from 'hds-react';
import { useAtomValue } from 'jotai';
import { getActingYearsAtom, shouldRenderPreviewAtom } from '../../store';

export const ActingYear = () => {
  const yearOptions = useAtomValue(getActingYearsAtom);
  const shouldRenderPreview = useAtomValue(shouldRenderPreviewAtom);

  if (shouldRenderPreview) {
    return null;
  }

  const options = (yearOptions ?? []).map((year) => ({ label: year, value: year }));

  return <Select options={options} />;
};
