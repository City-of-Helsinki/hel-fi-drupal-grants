import { useAtomValue } from 'jotai';
import { TextInput } from 'hds-react';
import { getFormConfigAtom } from '../store';

export const ApplicantInfo = () => {
  const { grantsProfile: {
    businessId,
    companyHome,
    companyHomePage,
    companyName,
    companyNameShort,
    foundingYear,
    registrationDate,
  } } = useAtomValue(getFormConfigAtom);

  const registrationDateString = new Date(registrationDate).toLocaleDateString('fi-FI');

  return (
    <>
      <div className='prh-content-block__content-row'>
        <TextInput
          id='companyName'
          className='prh-content-block__item'
          readOnly
          value={companyName}
          label={Drupal.t('Name of association')}
        />
        <TextInput
          id='businessId'
          className='prh-content-block__item'
          readOnly
          value={businessId}
          label={Drupal.t('Business ID')}
        />
        <TextInput
          id='registrationDate'
          className='prh-content-block__item'
          readOnly
          value={registrationDateString}
          label={Drupal.t('Date of registration')}
        />
      </div>
      <div className='prh-content-block__content-row'>
        <TextInput
          id='companyHome'
          className='prh-content-block__item'
          readOnly
          value={companyHome}
          label={Drupal.t('Municipality where the association is based (domicile)')}
        />
        <TextInput
          id='companyNameShort'
          className='prh-content-block__item'
          readOnly
          value={companyNameShort}
          label={Drupal.t('Abbreviated name')}
        />
        <TextInput
          id='foundingYear'
          className='prh-content-block__item'
          readOnly
          value={foundingYear}
          label={Drupal.t('Year of establishment')}
        />
        <TextInput
          id='companyHomePage'
          className='prh-content-block__item'
          readOnly
          value={companyHomePage}
          label={Drupal.t('Website address')}
        />
      </div>
    </>
  );
}
