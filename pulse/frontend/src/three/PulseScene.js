import * as THREE from 'three';

const STATUS_STYLES = {
  calm: {
    color: 0x7dd3c7,
    accent: 0xbfe9ff,
    speed: 1,
    pulse: 0.06,
    particles: 0.52,
  },
  irritated: {
    color: 0xff9f7a,
    accent: 0xff6f91,
    speed: 1.8,
    pulse: 0.15,
    particles: 0.9,
  },
  sluggish: {
    color: 0x9fc7ff,
    accent: 0xc9d8ff,
    speed: 0.42,
    pulse: 0.035,
    particles: 0.3,
  },
  anxious: {
    color: 0xffd08a,
    accent: 0xff9fb2,
    speed: 1.15,
    pulse: 0.09,
    particles: 0.78,
  },
  excited: {
    color: 0xaee8de,
    accent: 0xffb7d5,
    speed: 1.45,
    pulse: 0.12,
    particles: 1,
  },
  sleepy: {
    color: 0xc9d8ff,
    accent: 0xe7f2ff,
    speed: 0.28,
    pulse: 0.028,
    particles: 0.2,
  },
};

export class PulseScene {
  constructor(container) {
    this.container = container;
    this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    this.clock = new THREE.Clock();
    this.pointer = new THREE.Vector2(0, 0);
    this.target = {
      color: new THREE.Color(STATUS_STYLES.calm.color),
      accent: new THREE.Color(STATUS_STYLES.calm.accent),
      speed: STATUS_STYLES.calm.speed,
      pulse: STATUS_STYLES.calm.pulse,
      particleOpacity: STATUS_STYLES.calm.particles,
    };
    this.current = {
      speed: STATUS_STYLES.calm.speed,
      pulse: STATUS_STYLES.calm.pulse,
    };
    this.action = null;
    this.particleSeeds = [];
    this.onPointerMove = this.onPointerMove.bind(this);
    this.onResize = this.onResize.bind(this);
    this.animate = this.animate.bind(this);
    this.init();
  }

  init() {
    try {
      this.renderer = new THREE.WebGLRenderer({
        antialias: true,
        alpha: true,
        powerPreference: 'high-performance',
      });
    } catch (error) {
      this.createFallback();
      return;
    }

    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
    this.renderer.outputColorSpace = THREE.SRGBColorSpace;
    this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
    this.renderer.toneMappingExposure = 1.08;
    this.renderer.setClearColor(0x000000, 0);
    this.container.appendChild(this.renderer.domElement);

    this.scene = new THREE.Scene();
    this.camera = new THREE.PerspectiveCamera(38, 1, 0.1, 100);
    this.camera.position.set(0, 0.35, 6.2);
    this.camera.lookAt(0, 0.1, 0);

    this.addLights();
    this.addPet();
    this.addParticles();
    this.addGround();

    window.addEventListener('pointermove', this.onPointerMove, { passive: true });
    window.addEventListener('resize', this.onResize);

    this.onResize();
    this.animate();
  }

  addLights() {
    const hemisphere = new THREE.HemisphereLight(0xfff8f2, 0x7dd3c7, 2.1);
    this.scene.add(hemisphere);

    const key = new THREE.DirectionalLight(0xffffff, 2.6);
    key.position.set(3.5, 5, 4);
    this.scene.add(key);

    const rim = new THREE.PointLight(0xff9fb2, 1.5, 9);
    rim.position.set(-3.2, -0.8, 2.6);
    this.scene.add(rim);

    const cool = new THREE.PointLight(0xbfe9ff, 1.2, 9);
    cool.position.set(3.2, 1.2, -2.4);
    this.scene.add(cool);
  }

  addPet() {
    const detail = window.innerWidth < 700 ? 3 : 4;
    this.bodyGeometry = new THREE.IcosahedronGeometry(1.42, detail);
    this.basePositions = Float32Array.from(this.bodyGeometry.attributes.position.array);

    this.bodyMaterial = new THREE.MeshPhysicalMaterial({
      color: STATUS_STYLES.calm.color,
      roughness: 0.3,
      metalness: 0,
      clearcoat: 1,
      clearcoatRoughness: 0.22,
      emissive: STATUS_STYLES.calm.color,
      emissiveIntensity: 0.14,
      transparent: true,
      opacity: 0.97,
    });

    this.body = new THREE.Mesh(this.bodyGeometry, this.bodyMaterial);
    this.petGroup = new THREE.Group();
    this.petGroup.add(this.body);

    const core = new THREE.Mesh(
      new THREE.SphereGeometry(0.86, 32, 32),
      new THREE.MeshBasicMaterial({
        color: 0xffffff,
        transparent: true,
        opacity: 0.12,
        blending: THREE.AdditiveBlending,
        depthWrite: false,
      })
    );
    this.petGroup.add(core);

    this.addFace();
    this.scene.add(this.petGroup);
  }

  addFace() {
    const eyeGeometry = new THREE.SphereGeometry(0.12, 24, 24);
    const eyeMaterial = new THREE.MeshStandardMaterial({
      color: 0x31565c,
      roughness: 0.44,
      metalness: 0,
    });

    this.leftEye = new THREE.Mesh(eyeGeometry, eyeMaterial);
    this.leftEye.position.set(-0.38, 0.18, 1.27);
    this.leftEye.scale.set(1, 1.32, 0.52);

    this.rightEye = this.leftEye.clone();
    this.rightEye.position.x = 0.38;

    const highlightGeometry = new THREE.SphereGeometry(0.034, 16, 16);
    const highlightMaterial = new THREE.MeshBasicMaterial({ color: 0xffffff });
    const leftHighlight = new THREE.Mesh(highlightGeometry, highlightMaterial);
    leftHighlight.position.set(-0.42, 0.23, 1.35);
    const rightHighlight = leftHighlight.clone();
    rightHighlight.position.x = 0.34;

    this.mouth = new THREE.Mesh(
      new THREE.TorusGeometry(0.16, 0.025, 8, 24, Math.PI),
      new THREE.MeshStandardMaterial({ color: 0x31565c, roughness: 0.5 })
    );
    this.mouth.position.set(0, -0.18, 1.33);
    this.mouth.rotation.z = Math.PI;

    const blushGeometry = new THREE.CircleGeometry(0.13, 24);
    const blushMaterial = new THREE.MeshBasicMaterial({
      color: 0xff9fb2,
      transparent: true,
      opacity: 0.46,
      depthWrite: false,
    });
    this.leftBlush = new THREE.Mesh(blushGeometry, blushMaterial);
    this.leftBlush.position.set(-0.62, -0.04, 1.24);
    this.rightBlush = this.leftBlush.clone();
    this.rightBlush.position.x = 0.62;

    this.petGroup.add(
      this.leftEye,
      this.rightEye,
      leftHighlight,
      rightHighlight,
      this.mouth,
      this.leftBlush,
      this.rightBlush
    );
  }

  addParticles() {
    const mobile = window.innerWidth < 700;
    const count = this.reducedMotion ? 55 : mobile ? 90 : 170;
    const positions = new Float32Array(count * 3);

    for (let i = 0; i < count; i++) {
      const radius = 2 + Math.random() * 2.7;
      const theta = Math.random() * Math.PI * 2;
      const phi = Math.acos(2 * Math.random() - 1);
      const x = radius * Math.sin(phi) * Math.cos(theta);
      const y = radius * Math.cos(phi) * 0.75;
      const z = radius * Math.sin(phi) * Math.sin(theta);

      positions[i * 3] = x;
      positions[i * 3 + 1] = y;
      positions[i * 3 + 2] = z;

      this.particleSeeds.push({
        x,
        y,
        z,
        phase: Math.random() * Math.PI * 2,
        speed: 0.25 + Math.random() * 0.7,
      });
    }

    this.particleGeometry = new THREE.BufferGeometry();
    this.particleGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

    this.particleMaterial = new THREE.PointsMaterial({
      color: STATUS_STYLES.calm.accent,
      size: mobile ? 0.06 : 0.052,
      transparent: true,
      opacity: STATUS_STYLES.calm.particles,
      depthWrite: false,
      blending: THREE.AdditiveBlending,
    });

    this.particles = new THREE.Points(this.particleGeometry, this.particleMaterial);
    this.scene.add(this.particles);
  }

  addGround() {
    const shadow = new THREE.Mesh(
      new THREE.CircleGeometry(2.05, 64),
      new THREE.MeshBasicMaterial({
        color: 0x7dd3c7,
        transparent: true,
        opacity: 0.09,
        depthWrite: false,
      })
    );
    shadow.position.y = -1.55;
    shadow.rotation.x = -Math.PI / 2;
    this.scene.add(shadow);

    this.ripple = new THREE.Mesh(
      new THREE.RingGeometry(1.55, 1.58, 64),
      new THREE.MeshBasicMaterial({
        color: 0xbfe9ff,
        transparent: true,
        opacity: 0,
        depthWrite: false,
        side: THREE.DoubleSide,
      })
    );
    this.ripple.position.y = -1.52;
    this.ripple.rotation.x = -Math.PI / 2;
    this.scene.add(this.ripple);
  }

  setState(state = {}, metrics = {}) {
    const style = STATUS_STYLES[state.status] || STATUS_STYLES.calm;
    this.target.color.setHex(style.color);
    this.target.accent.setHex(style.accent);
    this.target.speed = style.speed;
    this.target.pulse = style.pulse;
    this.target.particleOpacity = style.particles;
    this.state = state;
    this.metrics = metrics;

    if (this.mouth) {
      this.mouth.scale.y = state.status === 'sleepy' || state.status === 'sluggish' ? 0.62 : 1;
    }
  }

  playAction(action) {
    this.action = {
      type: action,
      startedAt: performance.now(),
      duration: 950,
    };
  }

  animate() {
    if (!this.renderer) {
      return;
    }

    requestAnimationFrame(this.animate);

    const elapsed = this.clock.getElapsedTime();
    const motionScale = this.reducedMotion ? 0.25 : 1;
    const now = performance.now();

    this.current.speed += (this.target.speed - this.current.speed) * 0.045;
    this.current.pulse += (this.target.pulse - this.current.pulse) * 0.045;

    const speed = this.current.speed * motionScale;
    const pulse = this.current.pulse;
    const breath = 1 + Math.sin(elapsed * speed * 1.6) * pulse;

    this.body.scale.set(
      breath,
      1 + Math.sin(elapsed * speed * 1.2) * pulse * 0.72,
      breath
    );
    this.body.position.y = Math.sin(elapsed * speed * 0.9) * 0.08;

    this.updateBody(elapsed, speed);
    this.updateParticles(elapsed, speed);
    this.updateAction(now);

    this.petGroup.rotation.y += (this.pointer.x * 0.24 - this.petGroup.rotation.y) * 0.045;
    this.petGroup.rotation.x += (-this.pointer.y * 0.1 - this.petGroup.rotation.x) * 0.045;

    this.bodyMaterial.color.lerp(this.target.color, 0.045);
    this.bodyMaterial.emissive.lerp(this.target.color, 0.045);
    this.particleMaterial.color.lerp(this.target.accent, 0.04);
    this.particleMaterial.opacity += (this.target.particleOpacity - this.particleMaterial.opacity) * 0.05;

    this.renderer.render(this.scene, this.camera);
  }

  updateBody(elapsed, speed) {
    const positions = this.bodyGeometry.attributes.position;
    const array = positions.array;
    const base = this.basePositions;
    const amplitude = 0.032 * (1 + Math.max(0, this.target.speed - 1) * 0.35);

    for (let i = 0; i < array.length; i += 3) {
      const bx = base[i];
      const by = base[i + 1];
      const bz = base[i + 2];
      const length = Math.hypot(bx, by, bz) || 1;
      const wave = Math.sin(elapsed * speed * 2.15 + i * 0.017) * amplitude;

      array[i] = bx + (bx / length) * wave;
      array[i + 1] = by + (by / length) * wave;
      array[i + 2] = bz + (bz / length) * wave;
    }

    positions.needsUpdate = true;
    this.bodyGeometry.computeVertexNormals();
  }

  updateParticles(elapsed, speed) {
    const positions = this.particleGeometry.attributes.position.array;

    this.particleSeeds.forEach((seed, index) => {
      const offset = index * 3;
      positions[offset] = seed.x + Math.sin(elapsed * 0.42 + seed.phase) * 0.08;
      positions[offset + 1] =
        seed.y + Math.sin(elapsed * seed.speed * speed + seed.phase) * 0.24;
      positions[offset + 2] = seed.z + Math.cos(elapsed * 0.36 + seed.phase) * 0.08;
    });

    this.particleGeometry.attributes.position.needsUpdate = true;
    this.particles.rotation.y = elapsed * 0.045 * speed;
  }

  updateAction(now) {
    if (!this.action) {
      return;
    }

    const progress = Math.min(1, (now - this.action.startedAt) / this.action.duration);
    const eased = Math.sin(progress * Math.PI);

    if (this.action.type === 'feed') {
      this.petGroup.scale.setScalar(1 + eased * 0.13);
      this.particleMaterial.color.lerp(new THREE.Color(0xff9fb2), 0.08);
    }

    if (this.action.type === 'pet') {
      this.petGroup.rotation.z = eased * 0.05;
      this.particleMaterial.color.lerp(new THREE.Color(0xffb7d5), 0.08);
    }

    if (this.action.type === 'cool') {
      this.ripple.material.opacity = (1 - progress) * 0.55;
      this.ripple.scale.setScalar(1 + progress * 1.35);
      this.particleMaterial.color.lerp(new THREE.Color(0xbfe9ff), 0.08);
    }

    if (this.action.type === 'play') {
      this.petGroup.position.y = Math.abs(Math.sin(progress * Math.PI * 2)) * 0.2;
      this.particleMaterial.color.lerp(new THREE.Color(0xffe7a3), 0.08);
    }

    if (progress >= 1) {
      this.action = null;
      this.petGroup.scale.setScalar(1);
      this.petGroup.rotation.z = 0;
      this.petGroup.position.y = 0;
      this.ripple.material.opacity = 0;
      this.ripple.scale.setScalar(1);
    }
  }

  onPointerMove(event) {
    this.pointer.x = (event.clientX / window.innerWidth) * 2 - 1;
    this.pointer.y = -((event.clientY / window.innerHeight) * 2 - 1);
  }

  onResize() {
    if (!this.renderer || !this.camera) {
      return;
    }

    const width = this.container.clientWidth || window.innerWidth;
    const height = this.container.clientHeight || window.innerHeight;

    this.camera.aspect = width / Math.max(1, height);
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(width, height, false);
  }

  createFallback() {
    this.container.innerHTML = '<div class="fallback-pet" role="img" aria-label="PULSE 生命体"></div>';
  }
}

