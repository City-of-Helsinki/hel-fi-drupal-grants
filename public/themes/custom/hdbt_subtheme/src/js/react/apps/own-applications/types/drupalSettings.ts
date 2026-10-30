declare namespace drupalSettings {
  const own_applications: {
    drafts: import('./ApplicationItem').ApplicationItem[];
    sent: import('./ApplicationItem').ApplicationItem[];
  };
}

declare namespace Drupal {
  // Provided by the grants_handler/grants-dialog library.
  const dialogFunctions: {
    createDialog: (options: {
      actionButtonCallback?: () => unknown;
      actionButtonCallbackIsAsync?: boolean;
      actionButtonIcon?: string;
      actionButtonText?: string;
      backButtonText?: string;
      closeButtonText?: string;
      customSelector?: string;
      dialogContent: string;
      dialogIcon?: string;
      dialogTitle?: string;
      dialogVariant?: string;
      enforceWait?: boolean;
    }) => void;
    removeDialog: (dialog: HTMLElement) => void;
  };

  class Message {
    add(message: string, options?: { type?: string }): string;
  }
}
