/**
 * Interactive Background Fireworks Canvas for Ashish Traders Fireworks
 * Provides ambient festive night sky fireworks and reacts dynamically to user clicks.
 */

(function () {
  const canvas = document.getElementById('bgFireworksCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let width, height;
  let particles = [];
  let rockets = [];
  let animId = null;

  function resize() {
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
  }

  window.addEventListener('resize', resize);
  resize();

  class MiniParticle {
    constructor(x, y, hue) {
      this.x = x;
      this.y = y;
      this.prevX = x;
      this.prevY = y;
      this.angle = Math.random() * Math.PI * 2;
      this.speed = Math.random() * 6 + 1.5;
      this.friction = 0.94;
      this.gravity = 0.2;
      this.hue = hue + (Math.random() * 30 - 15);
      this.alpha = 1;
      this.decay = Math.random() * 0.015 + 0.01;
    }

    update() {
      this.prevX = this.x;
      this.prevY = this.y;
      this.speed *= this.friction;
      this.x += Math.cos(this.angle) * this.speed;
      this.y += Math.sin(this.angle) * this.speed + this.gravity;
      this.alpha -= this.decay;
    }

    draw(context) {
      context.save();
      context.beginPath();
      context.moveTo(this.prevX, this.prevY);
      context.lineTo(this.x, this.y);
      context.strokeStyle = `hsla(${this.hue}, 100%, 65%, ${Math.max(0, this.alpha)})`;
      context.lineWidth = 1.8;
      context.stroke();
      context.restore();
    }
  }

  function createAmbientBurst(x, y, hue) {
    const count = 40 + Math.floor(Math.random() * 30);
    const chosenHue = hue || [45, 15, 350, 140, 200, 280][Math.floor(Math.random() * 6)];
    for (let i = 0; i < count; i++) {
      particles.push(new MiniParticle(x, y, chosenHue));
    }
  }

  // Handle user click / tap anywhere on page to trigger interactive cracker
  document.addEventListener('click', (e) => {
    // Avoid firing when clicking buttons, inputs or links
    if (e.target.closest('button, a, input, select, textarea, .glass-panel')) {
      return;
    }
    createAmbientBurst(e.clientX, e.clientY);
  });

  let lastAmbientTime = 0;

  function loop(currentTime) {
    animId = requestAnimationFrame(loop);

    ctx.globalCompositeOperation = 'destination-out';
    ctx.fillStyle = 'rgba(7, 8, 13, 0.2)';
    ctx.fillRect(0, 0, width, height);
    ctx.globalCompositeOperation = 'lighter';

    // Ambient random distant firework every 3.5 seconds
    if (currentTime - lastAmbientTime > 3500) {
      lastAmbientTime = currentTime;
      const x = Math.random() * width * 0.8 + width * 0.1;
      const y = Math.random() * height * 0.45 + 50;
      createAmbientBurst(x, y);
    }

    // Update & draw particles
    for (let i = particles.length - 1; i >= 0; i--) {
      particles[i].update();
      particles[i].draw(ctx);
      if (particles[i].alpha <= 0) {
        particles.splice(i, 1);
      }
    }
  }

  window.initBackgroundFireworks = function () {
    if (!animId) {
      loop(0);
    }
  };

  // Auto initialize background after short delay
  setTimeout(() => {
    window.initBackgroundFireworks();
  }, 1000);
})();
