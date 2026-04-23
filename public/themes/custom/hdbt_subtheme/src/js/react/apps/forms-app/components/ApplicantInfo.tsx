import { useAtomValue } from 'jotai';
import { getFormConfigAtom } from '../store';

const InfoField = ({ label, value }: { label: string; value: string | number }) => (
  <div className='prh-content-block__item'>
    <div className='prh-content-block__item__label'>{label}</div>
    <div className='prh-content-block__item__value'>{value}</div>
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

  const registrationDateString = new Date(registrationDate).toLocaleDateString('fi-FI');

  return (
    <>
      <div className='prh-content-block__content-row'>
        <InfoField label={Drupal.t('Name of association')} value={companyName} />
        <InfoField label={Drupal.t('Business ID')} value={businessId} />
        <InfoField label={Drupal.t('Date of registration')} value={registrationDateString} />
      </div>
      <div className='prh-content-block__content-row'>
        <InfoField label={Drupal.t('Municipality where the association is based (domicile)')} value={companyHome} />
        <InfoField label={Drupal.t('Abbreviated name')} value={companyNameShort} />
        <InfoField label={Drupal.t('Year of establishment')} value={foundingYear} />
        <InfoField label={Drupal.t('Website address')} value={companyHomePage} />
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

  const registrationDateString = registrationDate ? new Date(registrationDate).toLocaleDateString('fi-FI') : undefined;

  return (
    <div className='form-group field field-object'>
      <section className='hdbt-form--section grants-form--preview-section'>
        <h4 className='hdbt-form--section__title'>
          {Drupal.t(
            'Community for which the grant is being applied for',
            {},
            { context: 'Grants application: Preview' },
          )}
        </h4>
        <div className='hdbt-form--section__content'>
          <PreviewField label={Drupal.t('Name of association')} value={companyName} />
          <PreviewField label={Drupal.t('Business ID')} value={businessId} />
          <PreviewField label={Drupal.t('Date of registration')} value={registrationDateString} />
          <PreviewField
            label={Drupal.t('Municipality where the association is based (domicile)')}
            value={companyHome}
          />
          <PreviewField label={Drupal.t('Abbreviated name')} value={companyNameShort} />
          <PreviewField label={Drupal.t('Year of establishment')} value={foundingYear} />
          <PreviewField label={Drupal.t('Website address')} value={companyHomePage} />
        </div>
      </section>
    </div>
  );
};
