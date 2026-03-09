import { Button, IconDownloadCloud } from 'hds-react';
import { useState } from 'react';
import { secondaryButtonTheme } from '@/react/common/constants/buttonTheme';

export const SaveDraftButton = ({ saveDraft }: { saveDraft: () => void }) => {
  const [submitting, setSubmitting] = useState(false);

  const onClick = async () => {
    setSubmitting(true);
    saveDraft();
  };

  return (
    <Button
      disabled={submitting}
      iconStart={<IconDownloadCloud />}
      onClick={onClick}
      theme={secondaryButtonTheme}
      type='button'
      style={
        {
          '--computed-color-disabled': 'var(--color-black-90)',
          '--computed-border-color-disabled': 'var(--color-black-90)',
        } as React.CSSProperties
      }
    >
      {Drupal.t('Save as draft', {}, { context: 'Grants application: Draft' })}
    </Button>
  );
};
