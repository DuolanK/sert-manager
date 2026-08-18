import { useEffect } from 'react';
import { useRouter } from 'next/router';

export default function CertificatesEditRedirect() {
  const router = useRouter();
  const { id } = router.query;
  useEffect(() => {
    if (id) {
      router.replace(`/tasks/${id}/edit`);
    } else {
      router.replace('/tasks');
    }
  }, [id, router]);
  return null;
}
