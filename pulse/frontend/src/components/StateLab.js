import { setPublicDemoScenario } from '../api.js';

const SCENARIOS = [
  { key: 'cpu-high', label: 'CPU 高负载', hint: '模拟 CPU 繁忙，PULSE 会变得暴躁。' },
  { key: 'memory-high', label: '内存高占用', hint: '模拟内存不足，PULSE 会变得迟钝。' },
  { key: 'disk-full', label: '磁盘快满', hint: '模拟磁盘空间紧张，PULSE 会变得焦虑。' },
  { key: 'crowded', label: '访客激增', hint: '模拟多人同时访问，PULSE 会变得兴奋。' },
  { key: 'lonely', label: '长时间无人', hint: '模拟长时间无人访问，PULSE 会进入睡眠。' },
  { key: 'calm', label: '恢复平静', hint: '清除模拟指标，让 PULSE 回到实时状态。' },
];

export class StateLab {
  constructor({ root, toggle, onChanged }) {
    this.root = root;
    this.toggle = toggle;
    this.onChanged = onChanged;
    this.closeButton = root?.querySelector('.drawer-close');
    this.list = document.querySelector('#public-scenario-list');
    this.message = document.querySelector('#public-scenario-message');

    this.toggle?.addEventListener('click', () => this.open());
    this.closeButton?.addEventListener('click', () => this.close());
    this.renderScenarios();
  }

  open() {
    this.root?.classList.add('is-open');
    this.root?.setAttribute('aria-hidden', 'false');
  }

  close() {
    this.root?.classList.remove('is-open');
    this.root?.setAttribute('aria-hidden', 'true');
  }

  renderScenarios() {
    if (!this.list) {
      return;
    }

    this.list.innerHTML = SCENARIOS.map((scenario) => `
      <button type="button" data-public-scenario="${escapeHtml(scenario.key)}">
        <strong>${escapeHtml(scenario.label)}</strong>
        <small>${escapeHtml(scenario.hint)}</small>
      </button>
    `).join('');

    this.list.querySelectorAll('[data-public-scenario]').forEach((button) => {
      button.addEventListener('click', () => this.apply(button.dataset.publicScenario));
    });
  }

  async apply(scenario) {
    if (!this.message) {
      return;
    }

    this.message.textContent = '正在切换状态...';

    try {
      await setPublicDemoScenario(scenario, 600);
      this.message.textContent = scenario === 'calm' ? '已恢复实时状态。' : '模拟状态已启用，10 分钟后自动恢复。';
      await this.onChanged?.();
    } catch (error) {
      this.message.textContent = error.message || '切换状态失败。';
    }
  }
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}
