declare namespace Drupal { // eslint-disable-line @typescript-eslint/no-unused-vars
  const cookieConsent: {
    initialized: () => boolean;
    loadFunction: (callback: () => void) => void;
    getConsentStatus: (categories: string[]) => string;
    setAcceptedCategories: (categories: string[]) => void;
  };
  function t(str: string, options?: object, context?: object): string;
  function formatPlural(count: string, singular: string, plural: string, args?: object, options?: object): string;
  function theme(id: string): string;
};
