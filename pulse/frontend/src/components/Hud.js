import { renderTimeline } from './Timeline.js';

const STATUS_LABELS = {
  calm: '平静',
  irritated: '暴躁',
  sluggish: '迟钝',
  anxious: '焦虑',
  excited: '兴奋',
  sleepy: '睡眠',
};

export class Hud {
  constructor() {
    this.statusLabel = document.querySelector('#status-label');
    this.sourceLabel = document.querySelector('#source-label');
    this.stageLabel = document.querySelector('#stage-label');
    this.moodValue = document.querySelector('#mood-value');
    this.energyValue = document.querySelector('#energy-value');
    this.trustValue = document.querySelector('#trust-value');
    this.moodBar = document.querySelector('#mood-bar');
    this.energyBar = document.querySelector('#energy-bar');
    this.trustBar = document.querySelector('#trust-bar');
    this.onlineValue = document.querySelector('#online-value');
    this.eventCount = document.querySelector('#event-count');
    this.eventsList = document.querySelector('#events-list');
    this.vitalHint = document.querySelector('#vital-hint');
    this.cooldownHint = document.querySelector('#cooldown-hint');
    this.timelineChart = document.querySelector('#timeline-chart');
    this.toast = document.querySelector('#toast');
    this.toastTimer = null;
  }

  update(snapshot) {
    const state = snapshot?.state || {};
    const metrics = snapshot?.metrics || {};
    const events = Array.isArray(snapshot?.events) ? snapshot.events : [];

    this.statusLabel.textContent = STATUS_LABELS[state.status] || '平静';
    this.sourceLabel.textContent = metrics.source === 'demo' ? '演示数据' : '实时数据';
    this.stageLabel.textContent = stageLabel(state.stage);
    this.moodValue.textContent = formatNumber(state.mood);
    this.energyValue.textContent = formatNumber(state.energy);
    this.trustValue.textContent = formatNumber(state.trust);
    this.moodBar.style.width = `${clamp(state.mood)}%`;
    this.energyBar.style.width = `${clamp(state.energy)}%`;
    this.trustBar.style.width = `${clamp(state.trust)}%`;
    this.onlineValue.textContent = `${snapshot?.online_count ?? 0} 在线`;
    this.eventCount.textContent = `${events.length} 条`;
    this.vitalHint.textContent = hintForStatus(state.status, metrics);

    this.renderEvents(events);
  }

  renderTimeline(points) {
    renderTimeline(this.timelineChart, points);
  }

  renderEvents(events) {
    if (!this.eventsList) {
      return;
    }

    if (!events.length) {
      this.eventsList.innerHTML = '<li><time>--:--</time><span>等待第一条事件。</span></li>';
      return;
    }

    this.eventsList.innerHTML = events
      .slice(-8)
      .reverse()
      .map((event) => {
        const time = String(event.created_at || '').slice(11, 16);
        return `<li><time>${escapeHtml(time || '--:--')}</time><span>${escapeHtml(event.message || '')}</span></li>`;
      })
      .join('');
  }

  setCooldown(seconds, message = '') {
    if (!this.cooldownHint) {
      return;
    }

    if (seconds <= 0) {
      this.cooldownHint.textContent = message;
      return;
    }

    this.cooldownHint.textContent = `冷却中 ${seconds} 秒`;
  }

  showToast(message) {
    if (!this.toast) {
      return;
    }

    this.toast.textContent = message;
    this.toast.classList.add('is-visible');

    window.clearTimeout(this.toastTimer);
    this.toastTimer = window.setTimeout(() => {
      this.toast.classList.remove('is-visible');
    }, 3200);
  }
}

function hintForStatus(status, metrics) {
  const memory = Number(metrics?.memory_used_pct || 0);
  const disk = Number(metrics?.disk_used_pct || 0);
  const cpu = Number(metrics?.cpu_load || 0);

  if (status === 'irritated') {
    return `CPU 负载 ${cpu.toFixed(2)}，它的脉冲变快了。`;
  }

  if (status === 'sluggish') {
    return `内存占用 ${memory.toFixed(1)}%，它的动作慢了下来。`;
  }

  if (status === 'anxious') {
    return `磁盘占用 ${disk.toFixed(1)}%，它有点不安。`;
  }

  if (status === 'excited') {
    return '访客变多了，它的颜色比平时更亮。';
  }

  if (status === 'sleepy') {
    return '很久没有人来，它已经把呼吸放慢了。';
  }

  return '它正在感受这台服务器的呼吸。';
}

function stageLabel(stage) {
  return {
    seed: '种子',
    sprout: '幼体',
    bloom: '成体',
    guardian: '守护体',
  }[stage] || '幼体';
}

function formatNumber(value) {
  const number = Number(value);
  return Number.isFinite(number) ? String(Math.round(number)) : '--';
}

function clamp(value) {
  const number = Number(value);
  return Number.isFinite(number) ? Math.max(0, Math.min(100, number)) : 0;
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

