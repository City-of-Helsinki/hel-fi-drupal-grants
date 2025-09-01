import { Button, ButtonPresetTheme, ButtonVariant, IconDownloadCloud } from 'hds-react';
import { useState } from 'react';

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
      theme={ButtonPresetTheme.Black}
      type='button'
      variant={ButtonVariant.Supplementary}
    >
      {Drupal.t('Save as draft', {}, {context: 'Grants application: Draft'})}
    </Button>
};
