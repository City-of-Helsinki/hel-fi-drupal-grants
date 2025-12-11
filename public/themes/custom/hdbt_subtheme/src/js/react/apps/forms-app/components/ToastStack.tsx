import { Notification } from 'hds-react';
import { useAtomValue, useSetAtom } from 'jotai';
import { useEffect, useState } from 'react';

import { shiftNotificationsAtom, type SystemNotification, systemNotificationsAtom } from '../store';

export const ToastStack = () => {
  const notifications = useAtomValue(systemNotificationsAtom);
  const shiftNotifications = useSetAtom(shiftNotificationsAtom);
  const [currentNotification, setCurrentNotification] = useState<SystemNotification | null>(notifications[0] || null);

  // biome-ignore lint/correctness/useExhaustiveDependencies: @todo UHF-12501
  useEffect(() => {
    if (!currentNotification && notifications.length > 0) {
      setCurrentNotification(notifications[0]);
    }
  }, [currentNotification, notifications, setCurrentNotification]);

  const handleClose = () => {
    shiftNotifications();
    setCurrentNotification(null);
  };

  return currentNotification ? (
    <Notification
      autoClose
      onClose={handleClose}
      label={currentNotification.label}
      position='bottom-right'
      style={{ zIndex: 100 }}
      type={currentNotification.type}
    >
      {currentNotification.children}
    </Notification>
  ) : null;
};
