import axios from 'axios';

function getBasePath(): string {
  const body = document.body;
  if (!body) {
    return '';
  }
  return body.getAttribute('data-base-path') || '';
}

export const api = axios.create({
  baseURL: `${getBasePath()}/api`,
  headers: {
    Accept: 'application/json'
  }
});
