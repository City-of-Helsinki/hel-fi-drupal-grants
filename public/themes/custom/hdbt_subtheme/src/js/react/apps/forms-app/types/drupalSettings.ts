declare namespace drupalSettings {
  // eslint-disable-line @typescript-eslint/no-unused-vars
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
    use_empty_preview: boolean;
    use_preview: boolean;
    use_print?: boolean;
    print_url?: string;
  };
}
