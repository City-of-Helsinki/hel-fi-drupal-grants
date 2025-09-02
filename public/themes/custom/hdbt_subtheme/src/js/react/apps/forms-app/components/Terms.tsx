import { useAtom, useAtomValue } from 'jotai';
import { Checkbox, Notification } from 'hds-react';
import { finalAcceptanceAtom, getCurrentStepAtom } from '../store';

export const Terms = () => {
  const [finalAcceptance, setFinalAcceptance] = useAtom(finalAcceptanceAtom);
  const { id } = useAtomValue(getCurrentStepAtom)[1];
  
  if (id !== 'preview') {
    return null;
  }

  const { body, link_title } = drupalSettings.grants_react_form.terms;

  return (
    <div className="grant-terms">
      <div className="terms_block">
        {/* eslint-disable-next-line react/no-danger */}
        <div dangerouslySetInnerHTML={{__html: body}} />
      </div>
      <div>
        <Notification className='hds-notification' type='alert' label='Huom!'>
          Hyväksy ehdot ja lähetä hakemus
        </Notification>
        <Checkbox
          checked={finalAcceptance}
          id='final-acceptance'
          label={link_title}
          onClick={() => setFinalAcceptance(!finalAcceptance)}
          style={{
            fontWeight: 'bold'
          }}
        />
      </div>
    </div>
  );
};
