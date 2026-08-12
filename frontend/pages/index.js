import { useRouter } from 'next/router';
import { useEffect } from 'react';
import { getToken } from '../utils/api';

export default function Home() {
  const router = useRouter();

  useEffect(() => {
    if (getToken()) {
      router.replace('/certificates');
    } else {
      router.replace('/login');
    }
  }, [router]);

  return null;
}
