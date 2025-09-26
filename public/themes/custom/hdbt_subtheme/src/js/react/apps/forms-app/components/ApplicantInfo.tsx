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
      <div className='prh-content-block__content'>
        <TextInput
          id='companyName'
          className=''
          readOnly
          value={companyName}
          label={Drupal.t('Name of association')}
        />
        <TextInput
          id='businessId'
          className=''
          readOnly
          value={businessId}
          label={Drupal.t('Business ID')}
        />
        <TextInput
          id='registrationDate'
          className=''
          readOnly
          value={registrationDateString}
          label={Drupal.t('Date of registration')}
        />
      </div>
      <div className='prh-content-block__content'>
        <TextInput
          id='companyHome'
          className=''
          readOnly
          value={companyHome}
          label={Drupal.t('Municipality where the association is based (domicile)')}
        />
        <TextInput
          id='companyNameShort'
          className=''
          readOnly
          value={companyNameShort}
          label={Drupal.t('Abbreviated name')}
        />
        <TextInput
          id='foundingYear'
          className=''
          readOnly
          value={foundingYear}
          label={Drupal.t('Year of establishment')}
        />
        <TextInput
          id='companyHomePage'
          className=''
          readOnly
          value={companyHomePage}
          label={Drupal.t('Website address')}
        />
      </div>
    </>
  );
}
