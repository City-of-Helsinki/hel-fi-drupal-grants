import {
  StatusLabel as HDStatusLabel,
  IconAlertCircle,
  IconInfoCircle,
  type StatusLabelType,
} from 'hds-react';
import { useAtomValue } from 'jotai';

import { getSubmitStatusAtom } from '../store';
import { StatusLabels } from '../enum/StatusLabels';
import { SubmitStates } from '../enum/SubmitStates';

export const StatusLabel = () => {
  const submitStatus = useAtomValue(getSubmitStatusAtom);

  let type: StatusLabelType | undefined;
  let iconStart: React.ReactNode | undefined;
  if (submitStatus === SubmitStates.RECEIVED) {
    type = 'info';
    iconStart = <IconInfoCircle />;
  } else if (submitStatus === SubmitStates.REJECTED) {
    type = 'error';
    iconStart = <IconAlertCircle />;
  }

  return (
    <HDStatusLabel {...{ iconStart, type }}>
      {StatusLabels[submitStatus]}
    </HDStatusLabel>
  );
};
