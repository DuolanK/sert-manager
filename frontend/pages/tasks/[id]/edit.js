import { useState, useEffect } from 'react';
import { useRouter } from 'next/router';
import Link from 'next/link';
import { getTask, updateTask, getToken } from '../../../utils/api';

export default function EditTaskPage() {
  const router = useRouter();
  const { id } = router.query;
  const [form, setForm] = useState({ title: '', executor: '', due_date: '', completed: false });
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
        const data = await getTask(id);
        setForm({
          title: data.title,
          executor: data.executor || '',
          due_date: data.due_date || '',
          completed: data.completed,
        });
      } catch (err) {
        if (err.status === 401) {
          router.push('/login');
          return;
        }
        setError(err.message || 'Не удалось загрузить задачу');
      } finally {
        setLoading(false);
      }
    })();
  }, [id, router]);

  const handleChange = (e) => {
    const target = e.target;
    const value = target.type === 'checkbox' ? target.checked : target.value;
    setForm({ ...form, [target.name]: value });
    setErrors({ ...errors, [target.name]: null });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setErrors({});
    setSaving(true);

    const payload = { ...form };
    if (!payload.executor) delete payload.executor;
    if (!payload.due_date) delete payload.due_date;

    try {
      await updateTask(id, payload);
      router.push('/tasks');
    } catch (err) {
      if (err.errors) {
        const fieldErrors = {};
        Object.entries(err.errors).forEach(([key, msgs]) => {
          fieldErrors[key] = Array.isArray(msgs) ? msgs[0] : msgs;
        });
        setErrors(fieldErrors);
      } else {
        setError(err.message || 'Не удалось обновить задачу');
      }
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <div className="container"><div className="loading">Загрузка...</div></div>;
  }

  return (
    <div className="container">
      <div className="header">
        <h1>Редактировать задачу</h1>
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

          <div className="form-group checkbox-group">
            <label className="checkbox-label">
              <input
                name="completed"
                type="checkbox"
                checked={form.completed}
                onChange={handleChange}
              />
              Выполнено
            </label>
          </div>

          <div className="form-actions">
            <button type="submit" className="btn btn-primary" disabled={saving}>
              {saving ? 'Сохранение...' : 'Сохранить'}
            </button>
            <Link href="/tasks" className="btn btn-secondary">Отмена</Link>
          </div>
        </form>
      </div>
    </div>
  );
}
