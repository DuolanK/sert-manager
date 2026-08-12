import { useState, useEffect, useCallback } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/router';
import { getCertificates, deleteCertificate, getToken, setToken } from '../../utils/api';

export default function CertificateListPage() {
  const router = useRouter();
  const [certificates, setCertificates] = useState([]);
  const [meta, setMeta] = useState(null);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = { page };
      if (search) params.search = search;
      if (status) params.status = status;
      const data = await getCertificates(params);
      setCertificates(data.data);
      setMeta(data.meta || { current_page: 1, last_page: 1 });
    } catch (err) {
      if (err.status === 401) {
        setToken(null);
        router.push('/login');
        return;
      }
      setError(err.message || 'Failed to load certificates');
    } finally {
      setLoading(false);
    }
  }, [page, search, status, router]);

  useEffect(() => {
    if (!getToken()) {
      router.push('/login');
      return;
    }
    fetchData();
  }, [fetchData, router]);

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await deleteCertificate(deleteTarget.id);
      setDeleteTarget(null);
      fetchData();
    } catch (err) {
      setError(err.message || 'Failed to delete');
    } finally {
      setDeleting(false);
    }
  };

  const handleLogout = () => {
    setToken(null);
    router.push('/login');
  };

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ru-RU');
  };

  const formatPrice = (price) => {
    return Number(price).toLocaleString('ru-RU', { style: 'currency', currency: 'RUB' });
  };

  const statusLabel = (s) => {
    const map = { active: 'Active', expired: 'Expired', redeemed: 'Redeemed' };
    return map[s] || s;
  };

  return (
    <div className="container">
      <div className="header">
        <h1>Duo sert manager</h1>
        <h1>Сертификаты</h1>
        <div style={{display: 'flex', gap: 8}}>
          <Link href="/certificates/create" className="btn btn-primary">+ Создать</Link>
          <button onClick={handleLogout} className="btn btn-secondary">Выйти</button>
        </div>
      </div>

      {error && <div className="error">{error}</div>}

      <div className="toolbar">
        <input
          type="text"
          placeholder="поиск по названию..."
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1); }}
        />
        <select value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }}>
          <option value="">All statuses</option>
          <option value="active">Активен</option>
          <option value="expired">Просрочен</option>
          <option value="redeemed">Использован</option>
        </select>
      </div>

      {loading ? (
        <div className="loading">Загрузка..</div>
      ) : certificates.length === 0 ? (
        <div className="loading">Не найдено</div>
      ) : (
        certificates.map((cert) => (
          <div key={cert.id} className="card">
            <div className="card-header">
              <div>
                <div className="card-title">{cert.name}</div>
                <span className={`badge badge-${cert.status}`}>{statusLabel(cert.status)}</span>
              </div>
              <div className="card-actions">
                <Link href={`/certificates/${cert.id}/edit`} className="btn btn-primary btn-sm">Edit</Link>
                <button
                  className="btn btn-danger btn-sm"
                  onClick={() => setDeleteTarget(cert)}
                >Delete</button>
              </div>
            </div>
            <div className="description">Цена: {formatPrice(cert.price)}</div>
            <div className="description">Срок действия истекает: {formatDate(cert.expires_at)}</div>
            <div className="description">Создан: {formatDate(cert.created_at)}</div>
          </div>
        ))
      )}

      {meta && meta.last_page > 1 && (
        <div className="pagination">
          <button disabled={page <= 1} onClick={() => setPage(page - 1)}>Prev</button>
          {Array.from({ length: meta.last_page }, (_, i) => i + 1).map((p) => (
            <button key={p} className={p === page ? 'active' : ''} onClick={() => setPage(p)}>
              {p}
            </button>
          ))}
          <button disabled={page >= meta.last_page} onClick={() => setPage(page + 1)}>Next</button>
        </div>
      )}

      {deleteTarget && (
        <div className="modal-overlay">
          <div className="modal">
            <h3>Удалить сертификат</h3>
            <p>Вы уверены? "{deleteTarget.name}"?</p>
            <div className="modal-actions">
              <button className="btn btn-secondary" onClick={() => setDeleteTarget(null)}>Cancel</button>
              <button className="btn btn-danger" onClick={handleDelete} disabled={deleting}>
                {deleting ? 'Deleting...' : 'Delete'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
