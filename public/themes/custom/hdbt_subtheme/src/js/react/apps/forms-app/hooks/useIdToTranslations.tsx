import { useTranslation } from 'react-i18next';

export const useIdToTranslations = (id: string): {
  addText?: string;
  title?: string;
  description?: string;
} => {
  const { t } = useTranslation();

  return {
    addText: t(`${id}.addText`),
    description: t(`${id}.description`),
    title: t(`${id}.title`),
  };
};
