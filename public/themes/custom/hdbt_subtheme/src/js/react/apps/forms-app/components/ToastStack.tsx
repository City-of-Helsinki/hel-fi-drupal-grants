import { Notification } from 'hds-react';
import { useAtomValue, useSetAtom } from 'jotai';
import { shiftNotificationsAtom, systemNotificationsAtom } from '../store';

export const ToastStack = () => {
  const notifications = useAtomValue(systemNotificationsAtom);
  const shiftNotifications = useSetAtom(shiftNotificationsAtom);
  const currentNotification = notifications.length && notifications[0];

  return currentNotification ?
      <Notification
        autoClose
        label={currentNotification.label}
        onClose={() => shiftNotifications()}
        position='top-right'
        style={{
          zIndex: 100,
        }}
        type={currentNotification.type}
      >
        {currentNotification.children}
      </Notification>
    : null
};