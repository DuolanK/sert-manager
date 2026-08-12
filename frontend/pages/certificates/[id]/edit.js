import { useState, useEffect } from 'react';
import { useRouter } from 'next/router';
import Link from 'next/link';
import { getCertificate, updateCertificate, getToken } from '../../../utils/api';

export default function EditCertificatePage() {
  const router = useRouter();
  const { id } = router.query;
  const [form, setForm] = useState({ name: '', price: '', expires_at: '', status: 'active' });
  const [error, setError] = useState('');
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (!getToken()) {
      router.push('/login');
      return;
    }
    if (!id) return;

    (async () => {
      try {
        const data = await getCertificate(id);
        setForm({
          name: data.name,
          price: data.price,
          expires_at: data.expires_at,
          status: data.status,
        });
      } catch (err) {
        if (err.status === 401) {
          router.push('/login');
          return;
        }
        setError(err.message || 'Failed to load certificate');
      } finally {
        setLoading(false);
      }
    })();
  }, [id, router]);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
    setErrors({ ...errors, [e.target.name]: null });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setErrors({});
    setSaving(true);

    try {
      await updateCertificate(id, form);
      router.push('/certificates');
    } catch (err) {
      if (err.errors) {
        const fieldErrors = {};
        Object.entries(err.errors).forEach(([key, msgs]) => {
          fieldErrors[key] = Array.isArray(msgs) ? msgs[0] : msgs;
        });
        setErrors(fieldErrors);
      } else {
        setError(err.message || 'Failed to update certificate');
      }
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <div className="container"><div className="loading">Loading...</div></div>;
  }

  return (
    <div className="container">
      <div className="header">
        <h1>Edit Certificate</h1>
        <Link href="/certificates" className="btn btn-secondary">Back</Link>
      </div>

      {error && <div className="error">{error}</div>}

      <div className="card">
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Name *</label>
            <input name="name" value={form.name} onChange={handleChange} required />
            {errors.name && <div className="error" style={{ marginTop: 4 }}>{errors.name}</div>}
          </div>

          <div className="form-group">
            <label>Price *</label>
            <input name="price" type="number" step="0.01" min="0.01" value={form.price} onChange={handleChange} required />
            {errors.price && <div className="error" style={{ marginTop: 4 }}>{errors.price}</div>}
          </div>

          <div className="form-group">
            <label>Expiration Date *</label>
            <input name="expires_at" type="date" value={form.expires_at} onChange={handleChange} required />
            {errors.expires_at && <div className="error" style={{ marginTop: 4 }}>{errors.expires_at}</div>}
          </div>

          <div className="form-group">
            <label>Status</label>
            <select name="status" value={form.status} onChange={handleChange}>
              <option value="active">Active</option>
              <option value="redeemed">Redeemed</option>
              <option value="expired">Expired</option>
            </select>
          </div>

          <div className="form-actions">
            <button type="submit" className="btn btn-primary" disabled={saving}>
              {saving ? 'Saving...' : 'Save'}
            </button>
            <Link href="/certificates" className="btn btn-secondary">Cancel</Link>
          </div>
        </form>
      </div>
    </div>
  );
}
