import { useAtomValue } from 'jotai';
import { getFormConfigAtom } from '../store';

const InfoField = ({ label, value }: { label: string; value: string | number | undefined }) => (
  <div className='prh-content-block__item'>
    <div className='prh-content-block__item__label'>{label}</div>
    <div className='prh-content-block__item__value'>{value ?? '-'}</div>
  </div>
);

export const ApplicantInfo = () => {
  const {
    grantsProfile: {
      businessId,
      companyHome,
      companyHomePage,
      companyName,
      companyNameShort,
      foundingYear,
      registrationDate,
    },
  } = useAtomValue(getFormConfigAtom);

  const registrationDateString = registrationDate ? new Date(registrationDate).toLocaleDateString('fi-FI') : '-';

  return (
    <>
      <div className='prh-content-block__content-row'>
        <InfoField label={Drupal.t('Name of association', {}, { context: 'Grants application' })} value={companyName} />
        <InfoField label={Drupal.t('Business ID', {}, { context: 'Grants application' })} value={businessId} />
        <InfoField
          label={Drupal.t('Date of registration', {}, { context: 'Grants application' })}
          value={registrationDateString}
        />
      </div>
      <div className='prh-content-block__content-row'>
        <InfoField
          label={Drupal.t(
            'Municipality where the association is based (domicile)',
            {},
            { context: 'Grants application' },
          )}
          value={companyHome}
        />
        <InfoField
          label={Drupal.t('Abbreviated name', {}, { context: 'Grants application' })}
          value={companyNameShort}
        />
        <InfoField
          label={Drupal.t('Year of establishment', {}, { context: 'Grants application' })}
          value={foundingYear}
        />
        <InfoField label={Drupal.t('Website address', {}, { context: 'Grants application' })} value={companyHomePage} />
      </div>
    </>
  );
};

const PreviewField = ({ label, value }: { label: string; value: string | number | undefined }) => (
  <div className='form-group field field-string'>
    <span className='grants-form--preview-section__label'>{label}</span>
    {value ?? '-'}
  </div>
);

export const PreviewApplicantInfo = () => {
  const { grantsProfile } = useAtomValue(getFormConfigAtom);

  const isPrivatePerson = !grantsProfile.companyName;
  const isUnregisteredCommunity = !isPrivatePerson && !grantsProfile.registrationDate;
  const isRegisteredCommunity = !isPrivatePerson && !isUnregisteredCommunity;

  const sectionTitle = isRegisteredCommunity
    ? Drupal.t('Community for which the grant is being applied for', {}, { context: 'Grants application' })
    : Drupal.t('Applicant details', {}, { context: 'Grants application' });

  const registrationDateString = grantsProfile.registrationDate
    ? new Date(grantsProfile.registrationDate).toLocaleDateString('fi-FI')
    : undefined;

  return (
    <div className='form-group field field-object'>
      <section className='hdbt-form--section grants-form--preview-section'>
        <h4 className='hdbt-form--section__title'>{sectionTitle}</h4>
        <div className='hdbt-form--section__content'>
          {isPrivatePerson && (
            <>
              <PreviewField
                label={Drupal.t('First name', {}, { context: 'Grants application' })}
                value={grantsProfile.firstName}
              />
              <PreviewField
                label={Drupal.t('Last name', {}, { context: 'Grants application' })}
                value={grantsProfile.lastName}
              />
              <PreviewField
                label={Drupal.t('Social security number', {}, { context: 'Grants application' })}
                value={grantsProfile.socialSecurityNumber}
              />
              <PreviewField
                label={Drupal.t('Email', {}, { context: 'Grants application' })}
                value={grantsProfile.email}
              />
              <PreviewField
                label={Drupal.t('Address', {}, { context: 'Grants application' })}
                value={
                  [
                    grantsProfile.addresses?.[0]?.street,
                    grantsProfile.addresses?.[0]?.postCode,
                    grantsProfile.addresses?.[0]?.city,
                    grantsProfile.addresses?.[0]?.country,
                  ]
                    .filter(Boolean)
                    .join(', ') || undefined
                }
              />
              <PreviewField
                label={Drupal.t('Phone number', {}, { context: 'Grants application' })}
                value={grantsProfile.phone_number}
              />
            </>
          )}
          {isUnregisteredCommunity && (
            <>
              <PreviewField
                label={Drupal.t('Name of association', {}, { context: 'Grants application' })}
                value={grantsProfile.companyName}
              />
              <PreviewField
                label={Drupal.t('First name', {}, { context: 'Grants application' })}
                value={grantsProfile.firstName}
              />
              <PreviewField
                label={Drupal.t('Last name', {}, { context: 'Grants application' })}
                value={grantsProfile.lastName}
              />
              <PreviewField
                label={Drupal.t('Social security number', {}, { context: 'Grants application' })}
                value={grantsProfile.socialSecurityNumber}
              />
              <PreviewField
                label={Drupal.t('Email', {}, { context: 'Grants application' })}
                value={grantsProfile.officials?.[0]?.email}
              />
              <PreviewField
                label={Drupal.t('Address', {}, { context: 'Grants application' })}
                value={
                  [
                    grantsProfile.addresses?.[0]?.street,
                    grantsProfile.addresses?.[0]?.postCode,
                    grantsProfile.addresses?.[0]?.city,
                    grantsProfile.addresses?.[0]?.country,
                  ]
                    .filter(Boolean)
                    .join(', ') || undefined
                }
              />
            </>
          )}
          {isRegisteredCommunity && (
            <>
              <PreviewField
                label={Drupal.t('Name of association', {}, { context: 'Grants application' })}
                value={grantsProfile.companyName}
              />
              <PreviewField
                label={Drupal.t('Business ID', {}, { context: 'Grants application' })}
                value={grantsProfile.businessId}
              />
              <PreviewField
                label={Drupal.t('Date of registration', {}, { context: 'Grants application' })}
                value={registrationDateString}
              />
              <PreviewField
                label={Drupal.t(
                  'Municipality where the association is based (domicile)',
                  {},
                  { context: 'Grants application' },
                )}
                value={grantsProfile.companyHome}
              />
              <PreviewField
                label={Drupal.t('Abbreviated name', {}, { context: 'Grants application' })}
                value={grantsProfile.companyNameShort}
              />
              <PreviewField
                label={Drupal.t('Year of establishment', {}, { context: 'Grants application' })}
                value={grantsProfile.foundingYear}
              />
              <PreviewField
                label={Drupal.t('Website address', {}, { context: 'Grants application' })}
                value={grantsProfile.companyHomePage}
              />
            </>
          )}
        </div>
      </section>
    </div>
  );
};
