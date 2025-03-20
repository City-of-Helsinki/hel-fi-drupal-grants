import { Button, ButtonPresetTheme, ButtonVariant, IconDownloadCloud } from 'hds-react';
import { useSetAtom } from 'jotai';
import { useState } from 'react';
import { pushNotificationAtom } from '../../store';

export const SaveDraftButton = ({
  saveDraft
}: {
  saveDraft: () => boolean;
}) => {
  const pushNotification = useSetAtom(pushNotificationAtom);
  const [submitting, setSubmitting] = useState(false);

  const onClick = async() => {
    setSubmitting(true);

    const result = await saveDraft();
    if (result) {
      pushNotification({
        children: <div>{Drupal.t('Application saved as draft.')}</div>,
        label: Drupal.t('Save successful.'),
        type: 'success',
      });
    }
    else {
      pushNotification({
        children: <div>{Drupal.t('Application could not be saved as draft.')}</div>,
        label: Drupal.t('Save failed.'),
        type: 'error',
      });
    }

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