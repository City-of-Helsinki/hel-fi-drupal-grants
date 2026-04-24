import { useCallback, useRef } from 'react';

export function useDebounceCallback<T extends (...args: Parameters<T>) => void>(
  callback: T,
  delay: number,
): (...args: Parameters<T>) => void {
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const callbackRef = useRef(callback);
  callbackRef.current = callback;

  return useCallback(
    (...args: Parameters<T>) => {
      if (timer.current) clearTimeout(timer.current);
      timer.current = setTimeout(() => callbackRef.current(...args), delay);
    },
    [delay],
  );
}
