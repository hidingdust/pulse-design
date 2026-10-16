export class ReportDrawer {
  constructor({ root, toggle }) {
    this.root = root;
    this.toggle = toggle;
    this.closeButton = root?.querySelector('.drawer-close');
    this.score = document.querySelector('#report-score');
    this.summary = document.querySelector('#report-summary');
    this.stage = document.querySelector('#report-stage');
    this.total = document.querySelector('#report-total');
    this.status = document.querySelector('#report-status');
    this.source = document.querySelector('#report-source');

    this.toggle?.addEventListener('click', () => this.open());
    this.closeButton?.addEventListener('click', () => this.close());
  }

  open() {
    this.root?.classList.add('is-open');
    this.root?.setAttribute('aria-hidden', 'false');
  }

  close() {
    this.root?.classList.remove('is-open');
    this.root?.setAttribute('aria-hidden', 'true');
  }

  render(report) {
    if (!report) {
      return;
    }

    if (this.score) {
      this.score.textContent = String(report.score ?? '--');
    }

    if (this.summary) {
      this.summary.textContent = report.summary || '等待心跳数据。';
    }

    if (this.stage) {
      this.stage.textContent = report.stage_label || '--';
    }

    if (this.total) {
      this.total.textContent = `${report.total_interactions ?? 0} 次`;
    }

    if (this.status) {
      this.status.textContent = statusLabel(report.status);
    }

    if (this.source) {
      this.source.textContent = report.metrics?.source === 'demo' ? '演示数据' : '实时数据';
    }
  }
}

function statusLabel(status) {
  return {
    calm: '平静',
    irritated: '暴躁',
    sluggish: '迟钝',
    anxious: '焦虑',
    excited: '兴奋',
    sleepy: '睡眠',
  }[status] || '平静';
}

