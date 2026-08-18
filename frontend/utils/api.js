const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:6162/api';

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

  const data = await res.json();

  if (!res.ok) {
    const error = new Error(data.message || 'Request failed');
    error.status = res.status;
    error.errors = data.errors;
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
