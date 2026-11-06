import { Button, ButtonVariant, Dialog, IconAlertCircle, IconTrash, Notification, NotificationSize } from 'hds-react';
import { useId, useState } from 'react';
import { secondaryButtonTheme } from '@/react/common/constants/buttonTheme';
import { deleteDraft } from '../helpers/deleteDraft';

// HDS danger buttons don't set the hover and focus text colors, so they would
// inherit --color-focus from the page, e.g. green from the Claro admin theme.
const dangerButtonTheme = {
  '--color-focus': 'var(--color-white)',
  '--color-hover': 'var(--color-white)',
  '--color-hover-focus': 'var(--color-white)',
};

type DeleteDraftDialogProps = {
  close: () => void;
  // The element that opened the dialog, focused again when the dialog closes.
  focusAfterCloseElement?: HTMLElement;
  isOpen: boolean;
  url: string;
};

const DeleteDraftDialog = ({ close, focusAfterCloseElement, isOpen, url }: DeleteDraftDialogProps) => {
  const [isDeleting, setIsDeleting] = useState(false);
  const [hasError, setHasError] = useState(false);
  const titleId = useId();
  const descriptionId = useId();

  const onClose = () => {
    setHasError(false);
    close();
  };

  const onDelete = async () => {
    setIsDeleting(true);
    setHasError(false);

    const redirectUrl = await deleteDraft(url);
    if (redirectUrl) {
      window.location.href = redirectUrl;
      return;
    }

    setIsDeleting(false);
    setHasError(true);
  };

  return (
    <Dialog
      aria-labelledby={titleId}
      aria-describedby={descriptionId}
      close={onClose}
      closeButtonLabelText={Drupal.t('Close', {}, { context: 'grants_handler' })}
      focusAfterCloseElement={focusAfterCloseElement}
      id='own-applications__delete-draft-dialog'
      isOpen={isOpen}
      variant='danger'
    >
      <Dialog.Header
        id={titleId}
        title={Drupal.t('Delete application', {}, { context: 'grants_handler_dialog' })}
        iconStart={<IconAlertCircle />}
      />
      <Dialog.Content>
        <p id={descriptionId}>
          {Drupal.t('Are you sure you want to delete the application?', {}, { context: 'grants_handler_dialog' })}
        </p>
        {hasError && (
          <Notification type='error' size={NotificationSize.Small}>
            {Drupal.t(
              'Deleting application failed. Please try again or contact support.',
              {},
              { context: 'grants_handler_dialog' },
            )}
          </Notification>
        )}
      </Dialog.Content>
      <Dialog.ActionButtons>
        <Button
          disabled={isDeleting}
          iconStart={<IconTrash />}
          id='own-applications__delete-draft-confirm'
          onClick={onDelete}
          theme={dangerButtonTheme}
          variant={ButtonVariant.Danger}
        >
          {Drupal.t('Delete', {}, { context: 'grants_handler_dialog' })}
        </Button>
        <Button disabled={isDeleting} onClick={onClose} theme={secondaryButtonTheme} variant={ButtonVariant.Secondary}>
          {Drupal.t('Cancel', {}, { context: 'grants_handler_dialog' })}
        </Button>
      </Dialog.ActionButtons>
    </Dialog>
  );
};

export default DeleteDraftDialog;
