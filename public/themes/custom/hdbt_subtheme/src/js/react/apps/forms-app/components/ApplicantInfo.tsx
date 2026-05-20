import { useAtomValue } from 'jotai';
import { getApplicantTypeAtom, getFormConfigAtom } from '../store';

const InfoField = ({ label, value }: { label: string; value: string | number | undefined }) => (
  <div className='prh-content-block__item'>
    <div className='prh-content-block__item__label'>{label}</div>
    <div className='prh-content-block__item__value'>{value ?? '-'}</div>
  </div>
);

export const ApplicantInfo = () => {
  const { grantsProfile } = useAtomValue(getFormConfigAtom);
  const applicantType = useAtomValue(getApplicantTypeAtom);
  const address = grantsProfile.addresses?.[0];

  if (applicantType === 'private_person') {
    return (
      <>
        <div className='prh-content-block__content-row'>
          <InfoField
            label={Drupal.t('First name', {}, { context: 'Grants application' })}
            value={grantsProfile.firstName}
          />
          <InfoField
            label={Drupal.t('Last name', {}, { context: 'Grants application' })}
            value={grantsProfile.lastName}
          />
          <InfoField
            label={Drupal.t('Social security number', {}, { context: 'Grants application' })}
            value={grantsProfile.socialSecurityNumber}
          />
        </div>
        <div className='prh-content-block__content-row'>
          <InfoField label={Drupal.t('Email', {}, { context: 'Grants application' })} value={grantsProfile.email} />
          <InfoField
            label={Drupal.t('Street address', {}, { context: 'Grants application' })}
            value={address?.street}
          />
          <InfoField label={Drupal.t('City', {}, { context: 'Grants application' })} value={address?.city} />
        </div>
        <div className='prh-content-block__content-row'>
          <InfoField label={Drupal.t('Postal code', {}, { context: 'Grants application' })} value={address?.postCode} />
          <InfoField label={Drupal.t('Country', {}, { context: 'Grants application' })} value={address?.country} />
          <InfoField
            label={Drupal.t('Phone number', {}, { context: 'Grants application' })}
            value={grantsProfile.phone_number}
          />
        </div>
      </>
    );
  }

  if (applicantType === 'unregistered_community') {
    const officialEmail = grantsProfile.officials?.[0]?.email;
    return (
      <>
        <div className='prh-content-block__content-row'>
          <InfoField
            label={Drupal.t('Name of association', {}, { context: 'Grants application' })}
            value={grantsProfile.companyName}
          />
          <InfoField
            label={Drupal.t('First name', {}, { context: 'Grants application' })}
            value={grantsProfile.firstName}
          />
          <InfoField
            label={Drupal.t('Last name', {}, { context: 'Grants application' })}
            value={grantsProfile.lastName}
          />
        </div>
        <div className='prh-content-block__content-row'>
          <InfoField
            label={Drupal.t('Social security number', {}, { context: 'Grants application' })}
            value={grantsProfile.socialSecurityNumber}
          />
          <InfoField label={Drupal.t('Email', {}, { context: 'Grants application' })} value={officialEmail} />
          <InfoField
            label={Drupal.t('Street address', {}, { context: 'Grants application' })}
            value={address?.street}
          />
        </div>
        <div className='prh-content-block__content-row'>
          <InfoField label={Drupal.t('City', {}, { context: 'Grants application' })} value={address?.city} />
          <InfoField label={Drupal.t('Postal code', {}, { context: 'Grants application' })} value={address?.postCode} />
          <InfoField label={Drupal.t('Country', {}, { context: 'Grants application' })} value={address?.country} />
        </div>
      </>
    );
  }

  const registrationDateString = grantsProfile.registrationDate
    ? new Date(grantsProfile.registrationDate).toLocaleDateString('fi-FI')
    : '-';

  return (
    <>
      <div className='prh-content-block__content-row'>
        <InfoField
          label={Drupal.t('Name of association', {}, { context: 'Grants application' })}
          value={grantsProfile.companyName}
        />
        <InfoField
          label={Drupal.t('Business ID', {}, { context: 'Grants application' })}
          value={grantsProfile.businessId}
        />
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
          value={grantsProfile.companyHome}
        />
        <InfoField
          label={Drupal.t('Abbreviated name', {}, { context: 'Grants application' })}
          value={grantsProfile.companyNameShort}
        />
        <InfoField
          label={Drupal.t('Year of establishment', {}, { context: 'Grants application' })}
          value={grantsProfile.foundingYear}
        />
        <InfoField
          label={Drupal.t('Website address', {}, { context: 'Grants application' })}
          value={grantsProfile.companyHomePage}
        />
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
  const applicantType = useAtomValue(getApplicantTypeAtom);

  const isPrivatePerson = applicantType === 'private_person';
  const isUnregisteredCommunity = applicantType === 'unregistered_community';
  const isRegisteredCommunity = applicantType === 'registered_community';

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
