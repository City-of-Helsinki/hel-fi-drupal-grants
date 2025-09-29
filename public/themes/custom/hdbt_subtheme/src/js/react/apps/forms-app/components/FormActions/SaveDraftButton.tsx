import { Button, IconDownloadCloud } from 'hds-react';
import { useState } from 'react';
import { secondaryButtonTheme } from '@/react/common/constants/buttonTheme';

export const SaveDraftButton = ({
  saveDraft
}: {
  saveDraft: () => void;
}) => {
  const [submitting, setSubmitting] = useState(false);

  const onClick = async() => {
    setSubmitting(true);
    saveDraft();
  }

  return <Button
      disabled={submitting}
      iconStart={<IconDownloadCloud />}
      onClick={onClick}
      className=''
      theme={secondaryButtonTheme}
      type='button'
    >
      {Drupal.t('Save as draft', {}, {context: 'Grants application: Draft'})}
    </Button>
};
