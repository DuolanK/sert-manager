import { useState, useEffect } from 'react';
import { useRouter } from 'next/router';
import Link from 'next/link';
import { createTask, getToken } from '../../utils/api';

export default function CreateTaskPage() {
  const router = useRouter();
  const [form, setForm] = useState({ title: '', executor: '', due_date: '' });
  const [error, setError] = useState('');
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!getToken()) {
      router.push('/login');
    }
  }, [router]);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
    setErrors({ ...errors, [e.target.name]: null });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setErrors({});
    setLoading(true);

    const payload = { ...form };
    if (!payload.executor) delete payload.executor;
    if (!payload.due_date) delete payload.due_date;

    try {
      await createTask(payload);
      router.push('/tasks');
    } catch (err) {
      if (err.errors) {
        const fieldErrors = {};
        Object.entries(err.errors).forEach(([key, msgs]) => {
          fieldErrors[key] = Array.isArray(msgs) ? msgs[0] : msgs;
        });
        setErrors(fieldErrors);
      } else {
        setError(err.message || 'Не удалось создать задачу');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="container">
      <div className="header">
        <h1>Создать задачу</h1>
        <Link href="/tasks" className="btn btn-secondary">Назад</Link>
      </div>

      {error && <div className="error">{error}</div>}

      <div className="card">
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Название *</label>
            <input name="title" value={form.title} onChange={handleChange} required />
            {errors.title && <div className="error" style={{ marginTop: 4 }}>{errors.title}</div>}
          </div>

          <div className="form-group">
            <label>Исполнитель</label>
            <input name="executor" value={form.executor} onChange={handleChange} />
            {errors.executor && <div className="error" style={{ marginTop: 4 }}>{errors.executor}</div>}
          </div>

          <div className="form-group">
            <label>Срок выполнения</label>
            <input name="due_date" type="date" value={form.due_date} onChange={handleChange} />
            {errors.due_date && <div className="error" style={{ marginTop: 4 }}>{errors.due_date}</div>}
          </div>

          <div className="form-actions">
            <button type="submit" className="btn btn-primary" disabled={loading}>
              {loading ? 'Создание...' : 'Создать'}
            </button>
            <Link href="/tasks" className="btn btn-secondary">Отмена</Link>
          </div>
        </form>
      </div>
    </div>
  );
}
