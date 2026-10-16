const WIDTH = 600;
const HEIGHT = 150;
const PAD = 18;

export function renderTimeline(container, points = []) {
  if (!container) {
    return;
  }

  const cleanPoints = Array.isArray(points) ? points.filter(Boolean) : [];

  if (cleanPoints.length < 2) {
    container.innerHTML = '<div class="empty">等待心跳数据</div>';
    return;
  }

  const maxIndex = Math.max(1, cleanPoints.length - 1);
  const x = (index) => PAD + (index / maxIndex) * (WIDTH - PAD * 2);
  const y = (value) => HEIGHT - PAD - (clamp(Number(value) || 0) / 100) * (HEIGHT - PAD * 2);

  const moodPath = buildPath(cleanPoints, 'mood', x, y);
  const energyPath = buildPath(cleanPoints, 'energy', x, y);
  const last = cleanPoints[cleanPoints.length - 1];
  const lastX = x(cleanPoints.length - 1);

  container.innerHTML = `
    <svg viewBox="0 0 ${WIDTH} ${HEIGHT}" role="img" aria-label="过去 24 小时心情和能量变化">
      <line class="grid" x1="${PAD}" x2="${WIDTH - PAD}" y1="${y(100)}" y2="${y(100)}" />
      <line class="grid" x1="${PAD}" x2="${WIDTH - PAD}" y1="${y(50)}" y2="${y(50)}" />
      <line class="grid" x1="${PAD}" x2="${WIDTH - PAD}" y1="${y(0)}" y2="${y(0)}" />
      <text class="axis-label" x="${PAD}" y="${y(100) - 5}">100</text>
      <text class="axis-label" x="${PAD}" y="${y(0) - 5}">0</text>
      <path class="mood-line" d="${moodPath}" />
      <path class="energy-line" d="${energyPath}" />
      <circle cx="${lastX}" cy="${y(last.mood)}" r="4" fill="#ff9fb2" />
      <circle cx="${lastX}" cy="${y(last.energy)}" r="4" fill="#7dd3c7" />
    </svg>
  `;
}

function buildPath(points, key, x, y) {
  return points
    .map((point, index) => {
      const command = index === 0 ? 'M' : 'L';
      return `${command} ${x(index).toFixed(1)} ${y(point[key]).toFixed(1)}`;
    })
    .join(' ');
}

function clamp(value) {
  return Math.max(0, Math.min(100, value));
}

