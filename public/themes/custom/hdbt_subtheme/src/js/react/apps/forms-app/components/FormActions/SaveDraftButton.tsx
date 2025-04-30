import { Button, ButtonPresetTheme, ButtonVariant, IconDownloadCloud } from 'hds-react';
import { useState } from 'react';

export const SaveDraftButton = ({
  saveDraft
}: {
  saveDraft: () => boolean;
}) => {
  const [submitting, setSubmitting] = useState(false);

  const onClick = async() => {
    setSubmitting(true);
    await saveDraft();
    setSubmitting(false);
  }

  return <Button
      disabled={submitting}
      iconStart={<IconDownloadCloud />}
      onClick={onClick}
      theme={ButtonPresetTheme.Black}
      type='button'
      variant={ButtonVariant.Supplementary}
    >
      {Drupal.t('Save as draft')}
    </Button>
};