import './styles.css';

import {
  getReport,
  getState,
  getTimeline,
  interact,
  setCsrfToken,
} from './api.js';
import { DemoPanel } from './components/DemoPanel.js';
import { Hud } from './components/Hud.js';
import { ReportDrawer } from './components/ReportDrawer.js';
import { PulseScene } from './three/PulseScene.js';

const sceneElement = document.querySelector('#scene');
const actionButtons = Array.from(document.querySelectorAll('[data-action]'));

const scene = new PulseScene(sceneElement);
const hud = new Hud();
const reportDrawer = new ReportDrawer({
  root: document.querySelector('#report-drawer'),
  toggle: document.querySelector('#report-toggle'),
});
const demoPanel = new DemoPanel({
  root: document.querySelector('#demo-panel'),
  toggle: document.querySelector('#demo-toggle'),
  onChanged: () => refresh({ timeline: true, report: true }),
});

let snapshot = null;
let polling = false;
let cooldownUntil = 0;
let lastTimelineAt = 0;
let lastReportAt = 0;
let errorCount = 0;

async function refresh({ timeline = false, report = false } = {}) {
  if (polling) {
    return;
  }

  polling = true;

  try {
    snapshot = await getState();
    setCsrfToken(snapshot.csrf_token);
    scene.setState(snapshot.state, snapshot.metrics);
    hud.update(snapshot);

    if (snapshot.animation) {
      scene.playAction(snapshot.animation);
    }

    errorCount = 0;
    const now = Date.now();

    if (timeline || now - lastTimelineAt > 30000) {
      lastTimelineAt = now;
      const timelineData = await getTimeline(24);
      hud.renderTimeline(timelineData.points || []);
    }

    if (report || now - lastReportAt > 60000) {
      lastReportAt = now;
      reportDrawer.render(await getReport());
    }
  } catch (error) {
    errorCount += 1;

    if (errorCount === 1 || errorCount % 15 === 0) {
      hud.showToast(error.message || 'PULSE 正在连接服务器。');
    }
  } finally {
    polling = false;
  }
}

async function runAction(action) {
  if (Date.now() < cooldownUntil) {
    return;
  }

  setButtonsDisabled(true);

  try {
    snapshot = await interact(action);
    setCsrfToken(snapshot.csrf_token);
    scene.setState(snapshot.state, snapshot.metrics);
    scene.playAction(action);
    hud.update(snapshot);
    startCooldown(snapshot.cooldown_seconds || 5);
  } catch (error) {
    hud.showToast(error.message || '这次互动没有成功。');
    startCooldown(2);
  }
}

function startCooldown(seconds) {
  cooldownUntil = Date.now() + seconds * 1000;
  setButtonsDisabled(true);

  const tick = () => {
    const remaining = Math.max(0, Math.ceil((cooldownUntil - Date.now()) / 1000));

    if (remaining > 0) {
      hud.setCooldown(remaining);
      window.setTimeout(tick, 250);
      return;
    }

    hud.setCooldown(0, '');
    setButtonsDisabled(false);
  };

  tick();
}

function setButtonsDisabled(disabled) {
  actionButtons.forEach((button) => {
    button.disabled = disabled;
  });
}

actionButtons.forEach((button) => {
  button.addEventListener('click', () => runAction(button.dataset.action));
});

document.querySelector('#report-toggle')?.addEventListener('click', async () => {
  try {
    reportDrawer.render(await getReport());
  } catch (error) {
    hud.showToast(error.message || '体检报告暂时不可用。');
  }
});

document.addEventListener('visibilitychange', () => {
  if (!document.hidden) {
    refresh({ timeline: true, report: true });
  }
});

refresh({ timeline: true, report: true });
window.setInterval(() => refresh(), 1000);

