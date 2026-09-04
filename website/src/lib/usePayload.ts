import {useEffect, useState} from 'react';
import useBaseUrl from '@docusaurus/useBaseUrl';
import type {Payload} from './types';

type State<T extends Payload = Payload> =
  | {status: 'loading'}
  | {status: 'ready'; payload: T}
  | {status: 'error'; message: string};

/**
 * Loads a generated payload from `static/data/`. Practice and Exam read
 * different files: the Practice payload is assembled at build time from the
 * learning pool alone, so holdout questions are absent from the file this hook
 * can fetch at all (§7.3).
 */
export default function usePayload<T extends Payload = Payload>(file: string): State<T> {
  const url = useBaseUrl(`/data/${file}`);
  const [state, setState] = useState<State<T>>({status: 'loading'});

  useEffect(() => {
    let cancelled = false;

    fetch(url, {cache: 'no-store'})
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        return response.json() as Promise<T>;
      })
      .then((payload) => {
        if (!cancelled) {
          setState({status: 'ready', payload});
        }
      })
      .catch((error: Error) => {
        if (!cancelled) {
          setState({status: 'error', message: error.message});
        }
      });

    return () => {
      cancelled = true;
    };
  }, [url]);

  return state;
}
