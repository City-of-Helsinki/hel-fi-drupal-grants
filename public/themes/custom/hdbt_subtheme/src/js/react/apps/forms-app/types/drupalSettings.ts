declare namespace drupalSettings {
  // eslint-disable-line @typescript-eslint/no-unused-vars
  const path: { currentLanguage: 'fi' | 'en' | 'sv' };
  const hdbt_cookie_banner: { settingsPageUrl: string };
  const helfi_react_search: { elastic_proxy_url: string; sentry_dsn_react: string; hakuvahti_url_set: boolean };
  const grants_react_form: {
    // TODO this one contains application type id aka form id.
    application_number: string;
    form_identifier: string;
    form: string;
    list_view_path: string;
    terms: { body: string; header: string; link_title: string };
    token: string;
    use_draft: boolean;
    // TODO this one contains the application number, the identifier for submission.
    real_application_number: string;
    use_preview: boolean;
  };
}
