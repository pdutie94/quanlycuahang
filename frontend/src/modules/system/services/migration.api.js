import { api } from '../../../shared/services/api';

export async function fetchMigrationInfo() {
  const response = await api.get('/migrations');
  return response.data;
}

export async function applyMigrations() {
  const response = await api.post('/migrations/apply');
  return response.data;
}

export async function runMigrationVersion(payload) {
  const response = await api.post('/migrations/run', payload);
  return response.data;
}
