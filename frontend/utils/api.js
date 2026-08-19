// Relative by default: requests go to the same origin and are proxied
// to the backend by a Next.js rewrite (see next.config.js). This avoids
// hardcoding a host/IP and keeps CORS + Private Network Access out of the loop.
const API_URL = process.env.NEXT_PUBLIC_API_URL || '/api';

let token = null;

if (typeof window !== 'undefined') {
  token = localStorage.getItem('token');
}

export function setToken(t) {
  token = t;
  if (t) {
    localStorage.setItem('token', t);
  } else {
    localStorage.removeItem('token');
  }
}

export function getToken() {
  return token;
}

async function request(path, options = {}) {
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const res = await fetch(`${API_URL}${path}`, {
    ...options,
    headers,
  });

  if (res.status === 204) return null;

  const contentType = res.headers.get('content-type') || '';
  const data = contentType.includes('application/json')
    ? await res.json()
    : await res.text().catch(() => '');

  if (!res.ok) {
    const message = typeof data === 'object' && data !== null
      ? data.message
      : `Server error (${res.status})`;
    const error = new Error(message || 'Request failed');
    error.status = res.status;
    error.errors = (typeof data === 'object' && data !== null) ? data.errors : undefined;
    throw error;
  }

  return data;
}

export function login(email, password) {
  return request('/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });
}

export function getTasks(params = {}) {
  const query = new URLSearchParams();
  if (params.search) query.set('search', params.search);
  if (params.completed) query.set('completed', params.completed);
  if (params.page) query.set('page', params.page);
  if (params.per_page) query.set('per_page', params.per_page);
  const qs = query.toString();
  return request(`/tasks${qs ? `?${qs}` : ''}`);
}

export function getTask(id) {
  return request(`/tasks/${id}`);
}

export function createTask(data) {
  return request('/tasks', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export function updateTask(id, data) {
  return request(`/tasks/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  });
}

export function deleteTask(id) {
  return request(`/tasks/${id}`, {
    method: 'DELETE',
  });
}
