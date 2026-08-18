import { useEffect } from 'react';
import { useRouter } from 'next/router';

export default function CertificatesCreateRedirect() {
  const router = useRouter();
  useEffect(() => {
    router.replace('/tasks/create');
  }, [router]);
  return null;
}
