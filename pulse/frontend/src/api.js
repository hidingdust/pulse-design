let csrfToken = '';

async function request(path, options = {}) {
  const response = await fetch(path, {
    credentials: 'same-origin',
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.headers || {}),
    },
  });

  let payload = null;

  try {
    payload = await response.json();
  } catch (error) {
    throw new Error('服务器没有返回有效数据。');
  }

  if (!response.ok || payload?.ok !== true) {
    throw new Error(payload?.error?.message || '请求失败，请稍后再试。');
  }

  return payload.data;
}

export function setCsrfToken(token) {
  csrfToken = token || '';
}

export function getCsrfToken() {
  return csrfToken;
}

export function getState() {
  return request('/api/state');
}

export function interact(action) {
  return request('/api/interactions', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken,
    },
    body: JSON.stringify({ action }),
  });
}

export function getTimeline(hours = 24) {
  return request(`/api/timeline?hours=${encodeURIComponent(hours)}`);
}

export function getReport() {
  return request('/api/report');
}

export function getEvents(sinceId = 0) {
  return request(`/api/events?since_id=${encodeURIComponent(sinceId)}`);
}

export function getDemoScenarios(token) {
  return request('/api/demo/scenarios', {
    headers: {
      'X-Demo-Token': token,
    },
  });
}

export function setDemoScenario(token, scenario, ttlSeconds = 600) {
  return request('/api/demo/scenario', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken,
      'X-Demo-Token': token,
    },
    body: JSON.stringify({
      scenario,
      ttl_seconds: ttlSeconds,
    }),
  });
}

