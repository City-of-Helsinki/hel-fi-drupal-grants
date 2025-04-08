import { useAtomValue, useSetAtom } from 'jotai';
import { Button } from 'hds-react';

import { getApplicationNumberAtom, setSubmitStatusAtom } from '../../store';

/**
 * Placeholder page for submitted forms.
 *
 * @return {JSX.Element} - The ready page
 */
export const Ready = () => {
  const applicationNumber = useAtomValue(getApplicationNumberAtom);
  const setSubmitStatus = useSetAtom(setSubmitStatusAtom);

  return (
    <div>
      <label htmlFor="application_number">
        Application number
        <input readOnly name='application_number' value={applicationNumber} />
      </label>
      <Button
        onClick={() => {
          setSubmitStatus('editing');
        }}
      >
        {Drupal.t('Edit application')}
      </Button>
    </div>
  );
};
