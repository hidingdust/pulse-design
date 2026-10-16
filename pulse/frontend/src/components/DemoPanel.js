import { getDemoScenarios, setDemoScenario } from '../api.js';

const TOKEN_KEY = 'pulse_demo_token';

export class DemoPanel {
  constructor({ root, toggle, onChanged }) {
    this.root = root;
    this.toggle = toggle;
    this.onChanged = onChanged;
    this.closeButton = root?.querySelector('.drawer-close');
    this.tokenInput = document.querySelector('#demo-token-input');
    this.list = document.querySelector('#demo-scenario-list');
    this.clearButton = document.querySelector('#demo-clear');
    this.token = sessionStorage.getItem(TOKEN_KEY) || '';

    if (this.tokenInput) {
      this.tokenInput.value = this.token;
    }

    this.toggle?.addEventListener('click', () => this.open());
    this.closeButton?.addEventListener('click', () => this.close());
    this.tokenInput?.addEventListener('change', () => this.load());
    this.tokenInput?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        this.load();
      }
    });
    this.clearButton?.addEventListener('click', () => this.apply('none'));
  }

  async open() {
    this.root?.classList.add('is-open');
    this.root?.setAttribute('aria-hidden', 'false');

    if (this.token) {
      await this.load();
    }
  }

  close() {
    this.root?.classList.remove('is-open');
    this.root?.setAttribute('aria-hidden', 'true');
  }

  syncToken() {
    this.token = this.tokenInput?.value?.trim() || '';

    if (this.token) {
      sessionStorage.setItem(TOKEN_KEY, this.token);
    } else {
      sessionStorage.removeItem(TOKEN_KEY);
    }
  }

  async load() {
    this.syncToken();

    if (!this.token) {
      this.renderMessage('输入演示令牌后加载场景。');
      return;
    }

    try {
      const data = await getDemoScenarios(this.token);
      this.render(data.scenarios || [], data.active?.scenario || null);
    } catch (error) {
      this.renderMessage(error.message || '演示场景加载失败。');
    }
  }

  render(scenarios, activeScenario) {
    if (!this.list) {
      return;
    }

    if (!scenarios.length) {
      this.renderMessage('没有可用场景。');
      return;
    }

    this.list.innerHTML = scenarios
      .map((scenario) => `
        <button type="button" data-scenario="${escapeHtml(scenario.key)}" class="${scenario.key === activeScenario ? 'is-active' : ''}">
          <strong>${escapeHtml(scenario.label)}</strong>
          <small>${escapeHtml(scenario.description)}</small>
        </button>
      `)
      .join('');

    this.list.querySelectorAll('[data-scenario]').forEach((button) => {
      button.addEventListener('click', () => this.apply(button.dataset.scenario));
    });
  }

  async apply(scenario) {
    this.syncToken();

    if (!this.token) {
      this.renderMessage('先输入演示令牌。');
      return;
    }

    try {
      const data = await setDemoScenario(this.token, scenario, 600);
      this.render(data.scenarios || [], data.active?.scenario || null);
      await this.onChanged?.();
    } catch (error) {
      this.renderMessage(error.message || '切换演示场景失败。');
    }
  }

  renderMessage(message) {
    if (!this.list) {
      return;
    }

    this.list.innerHTML = `<p class="hint">${escapeHtml(message)}</p>`;
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

