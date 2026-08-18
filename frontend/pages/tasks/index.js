import { useState, useEffect, useCallback } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/router';
import { getTasks, updateTask, deleteTask, getToken, setToken } from '../../utils/api';

export default function TaskListPage() {
  const router = useRouter();
  const [tasks, setTasks] = useState([]);
  const [meta, setMeta] = useState(null);
  const [search, setSearch] = useState('');
  const [filter, setFilter] = useState('');
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
      if (filter) params.completed = filter;
      const data = await getTasks(params);
      setTasks(data.data);
      setMeta(data.meta || { current_page: 1, last_page: 1 });
    } catch (err) {
      if (err.status === 401) {
        setToken(null);
        router.push('/login');
        return;
      }
      setError(err.message || 'Не удалось загрузить задачи');
    } finally {
      setLoading(false);
    }
  }, [page, search, filter, router]);

  useEffect(() => {
    if (!getToken()) {
      router.push('/login');
      return;
    }
    fetchData();
  }, [fetchData, router]);

  const handleToggle = async (task) => {
    try {
      await updateTask(task.id, { completed: !task.completed });
      fetchData();
    } catch (err) {
      setError(err.message || 'Не удалось обновить задачу');
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await deleteTask(deleteTarget.id);
      setDeleteTarget(null);
      fetchData();
    } catch (err) {
      setError(err.message || 'Не удалось удалить');
    } finally {
      setDeleting(false);
    }
  };

  const handleLogout = () => {
    setToken(null);
    router.push('/login');
  };

  const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('ru-RU');
  };

  return (
    <div className="container">
      <div className="header">
        <h1>Задачи</h1>
        <div style={{ display: 'flex', gap: 8 }}>
          <Link href="/tasks/create" className="btn btn-primary">+ Создать</Link>
          <button onClick={handleLogout} className="btn btn-secondary">Выйти</button>
        </div>
      </div>

      {error && <div className="error">{error}</div>}

      <div className="toolbar">
        <input
          type="text"
          placeholder="поиск по названию или исполнителю..."
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1); }}
        />
        <select value={filter} onChange={(e) => { setFilter(e.target.value); setPage(1); }}>
          <option value="">Все задачи</option>
          <option value="true">Выполненные</option>
          <option value="false">Невыполненные</option>
        </select>
      </div>

      {loading ? (
        <div className="loading">Загрузка...</div>
      ) : tasks.length === 0 ? (
        <div className="loading">Задач не найдено</div>
      ) : (
        tasks.map((task) => (
          <div key={task.id} className={`card ${task.completed ? 'card-completed' : ''}`}>
            <div className="card-header">
              <div className="card-title-row">
                <input
                  type="checkbox"
                  className="task-checkbox"
                  checked={task.completed}
                  onChange={() => handleToggle(task)}
                  title={task.completed ? 'Отметить как невыполненную' : 'Отметить как выполненную'}
                />
                <div>
                  <div className={`card-title ${task.completed ? 'title-done' : ''}`}>{task.title}</div>
                  <span className={`badge ${task.completed ? 'badge-done' : 'badge-active'}`}>
                    {task.completed ? 'Выполнено' : 'В работе'}
                  </span>
                </div>
              </div>
              <div className="card-actions">
                <Link href={`/tasks/${task.id}/edit`} className="btn btn-primary btn-sm">Изменить</Link>
                <button
                  className="btn btn-danger btn-sm"
                  onClick={() => setDeleteTarget(task)}
                >Удалить</button>
              </div>
            </div>
            <div className="description">Исполнитель: {task.executor || '—'}</div>
            <div className="description">Срок: {formatDate(task.due_date)}</div>
            <div className="description">Создана: {formatDate(task.created_at)}</div>
          </div>
        ))
      )}

      {meta && meta.last_page > 1 && (
        <div className="pagination">
          <button disabled={page <= 1} onClick={() => setPage(page - 1)}>Назад</button>
          {Array.from({ length: meta.last_page }, (_, i) => i + 1).map((p) => (
            <button key={p} className={p === page ? 'active' : ''} onClick={() => setPage(p)}>
              {p}
            </button>
          ))}
          <button disabled={page >= meta.last_page} onClick={() => setPage(page + 1)}>Вперёд</button>
        </div>
      )}

      {deleteTarget && (
        <div className="modal-overlay">
          <div className="modal">
            <h3>Удалить задачу</h3>
            <p>Вы уверены? "{deleteTarget.title}"?</p>
            <div className="modal-actions">
              <button className="btn btn-secondary" onClick={() => setDeleteTarget(null)}>Отмена</button>
              <button className="btn btn-danger" onClick={handleDelete} disabled={deleting}>
                {deleting ? 'Удаление...' : 'Удалить'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
