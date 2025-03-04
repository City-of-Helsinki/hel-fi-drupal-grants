import { useAtomValue } from 'jotai';
import { getFormConfigAtom } from '../store';
import { TextInput } from 'hds-react';

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
      <div className='applicant-info--from-grants'>
        <TextInput
          id='companyName'
          className='grants-handler-prefilled-field'
          readOnly
          value={companyName}
          label={Drupal.t('Name of association')}
        />
        <TextInput
          id='businessId'
          className='grants-handler-prefilled-field'
          readOnly
          value={businessId}
          label={Drupal.t('Business ID')}
        />
        <TextInput
          id='registrationDate'
          className='grants-handler-prefilled-field'
          readOnly
          value={registrationDateString}
          label={Drupal.t('Date of registration')}
        />
      </div>
      <div className='applicant-info--from-grants'>
        <TextInput
          id='companyHome'
          className='grants-handler-prefilled-field'
          readOnly
          value={companyHome}
          label={Drupal.t('Municipality where the association is based (domicile)')}
        />
        <TextInput
          id='companyNameShort'
          className='grants-handler-prefilled-field'
          readOnly
          value={companyNameShort}
          label={Drupal.t('Abbreviated name')}
        />
        <TextInput
          id='foundingYear'
          className='grants-handler-prefilled-field'
          readOnly
          value={foundingYear}
          label={Drupal.t('Year of establishment')}
        />
        <TextInput
          id='companyHomePage'
          className='grants-handler-prefilled-field'
          readOnly
          value={companyHomePage}
          label={Drupal.t('Website address')}
        />
      </div>
    </>
  );
}
