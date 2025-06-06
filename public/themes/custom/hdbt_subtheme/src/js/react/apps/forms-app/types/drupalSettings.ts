declare namespace drupalSettings { // eslint-disable-line @typescript-eslint/no-unused-vars
  const path: {
    currentLanguage: 'fi' | 'en' | 'sv';
  };
  const hdbt_cookie_banner: {
    settingsPageUrl: string;
  };
  const helfi_react_search: {
    // @todo UHF-10862 Remove cookie_privacy_url once the HDBT cookie banner module is in use.
    cookie_privacy_url: string;
    elastic_proxy_url: string;
    sentry_dsn_react: string;
    hakuvahti_url_set: boolean;
  };
  const grants_react_form: {
    application_number: string;
    form: string;
    token: string;
  };
};
