/**
 * Web Audio API Sound Synthesizer for Ashish Traders Fireworks
 * Realistic physical fireworks sound engine: Rocket Whistle, Explosive Boom, Crackle & Sparklers.
 */

class FireworksSoundEngine {
  constructor() {
    this.ctx = null;
    this.isMuted = false;
    this.isInitialized = false;

    // Check localStorage preference
    const saved = localStorage.getItem('at_fireworks_sound_muted');
    if (saved !== null) {
      this.isMuted = saved === 'true';
    }
  }

  init() {
    if (!this.ctx) {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (AudioContext) {
        this.ctx = new AudioContext();
        this.isInitialized = true;
      }
    }
    if (this.ctx && this.ctx.state === 'suspended') {
      this.ctx.resume();
    }
  }

  toggleMute() {
    this.init();
    this.isMuted = !this.isMuted;
    localStorage.setItem('at_fireworks_sound_muted', this.isMuted);
    this.updateAudioButtons();
    return this.isMuted;
  }

  updateAudioButtons() {
    const btns = document.querySelectorAll('.audio-toggle-btn');
    btns.forEach(btn => {
      if (this.isMuted) {
        btn.innerHTML = `
          <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
          </svg>
        `;
        btn.setAttribute('title', 'Sound Muted (Click to enable audio)');
      } else {
        btn.innerHTML = `
          <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
          </svg>
        `;
        btn.setAttribute('title', 'Sound Active (Click to mute)');
      }
    });
  }

  // Synthesize Rocket Whistling Ascent Sound
  playRocketWhistle(duration = 1.6) {
    if (this.isMuted) return;
    this.init();
    if (!this.ctx) return;

    const t = this.ctx.currentTime;
    
    // Main rising tone (Oscillator)
    const osc = this.ctx.createOscillator();
    const gain = this.ctx.createGain();

    osc.type = 'sawtooth';
    // Frequency climbs as rocket accelerates skyward
    osc.frequency.setValueAtTime(320, t);
    osc.frequency.exponentialRampToValueAtTime(1450, t + duration);

    // Whistle tremolo modulation
    const mod = this.ctx.createOscillator();
    const modGain = this.ctx.createGain();
    mod.frequency.setValueAtTime(24, t);
    modGain.gain.setValueAtTime(45, t);
    mod.connect(osc.frequency);
    mod.start(t);
    mod.stop(t + duration);

    // Filter to soften high buzz
    const filter = this.ctx.createBiquadFilter();
    filter.type = 'lowpass';
    filter.frequency.setValueAtTime(2500, t);

    // Envelope
    gain.gain.setValueAtTime(0.01, t);
    gain.gain.linearRampToValueAtTime(0.18, t + 0.2);
    gain.gain.linearRampToValueAtTime(0.22, t + duration * 0.8);
    gain.gain.exponentialRampToValueAtTime(0.001, t + duration);

    osc.connect(filter);
    filter.connect(gain);
    gain.connect(this.ctx.destination);

    osc.start(t);
    osc.stop(t + duration);

    // Add slight rocket jet flame roar (Noise)
    this.playNoiseRoar(duration, 0.08);
  }

  playNoiseRoar(duration, volume = 0.1) {
    if (!this.ctx) return;
    const t = this.ctx.currentTime;
    const bufferSize = this.ctx.sampleRate * duration;
    const buffer = this.ctx.createBuffer(1, bufferSize, this.ctx.sampleRate);
    const data = buffer.getChannelData(0);

    for (let i = 0; i < bufferSize; i++) {
      data[i] = Math.random() * 2 - 1;
    }

    const noise = this.ctx.createBufferSource();
    noise.buffer = buffer;

    const filter = this.ctx.createBiquadFilter();
    filter.type = 'bandpass';
    filter.frequency.setValueAtTime(400, t);
    filter.frequency.linearRampToValueAtTime(900, t + duration);

    const gain = this.ctx.createGain();
    gain.gain.setValueAtTime(0.01, t);
    gain.gain.linearRampToValueAtTime(volume, t + 0.1);
    gain.gain.exponentialRampToValueAtTime(0.001, t + duration);

    noise.connect(filter);
    filter.connect(gain);
    gain.connect(this.ctx.destination);

    noise.start(t);
    noise.stop(t + duration);
  }

  // Synthesize Thunderous Firecracker Explosion Boom
  playExplosionBoom() {
    if (this.isMuted) return;
    this.init();
    if (!this.ctx) return;

    const t = this.ctx.currentTime;

    // 1. Deep Sub-bass punch (Simulating acoustic pressure wave)
    const subOsc = this.ctx.createOscillator();
    const subGain = this.ctx.createGain();

    subOsc.type = 'sine';
    subOsc.frequency.setValueAtTime(140, t);
    subOsc.frequency.exponentialRampToValueAtTime(28, t + 0.6);

    subGain.gain.setValueAtTime(0.7, t);
    subGain.gain.exponentialRampToValueAtTime(0.001, t + 1.2);

    subOsc.connect(subGain);
    subGain.connect(this.ctx.destination);

    subOsc.start(t);
    subOsc.stop(t + 1.2);

    // 2. Gunpowder blast transient (Filtered White Noise)
    const bufferSize = this.ctx.sampleRate * 1.5;
    const buffer = this.ctx.createBuffer(1, bufferSize, this.ctx.sampleRate);
    const data = buffer.getChannelData(0);
    for (let i = 0; i < bufferSize; i++) {
      data[i] = (Math.random() * 2 - 1) * Math.exp(-i / (this.ctx.sampleRate * 0.25));
    }

    const blastNoise = this.ctx.createBufferSource();
    blastNoise.buffer = buffer;

    const blastFilter = this.ctx.createBiquadFilter();
    blastFilter.type = 'lowpass';
    blastFilter.frequency.setValueAtTime(800, t);
    blastFilter.frequency.exponentialRampToValueAtTime(120, t + 1.0);

    const blastGain = this.ctx.createGain();
    blastGain.gain.setValueAtTime(0.65, t);
    blastGain.gain.exponentialRampToValueAtTime(0.001, t + 1.5);

    blastNoise.connect(blastFilter);
    blastFilter.connect(blastGain);
    blastGain.connect(this.ctx.destination);

    blastNoise.start(t);
    blastNoise.stop(t + 1.5);

    // 3. Delayed Sparkling Willow Crackles
    setTimeout(() => {
      this.playCrackleCluster();
    }, 280);
  }

  // Sparkling golden crackles after sky shot explosion
  playCrackleCluster() {
    if (this.isMuted || !this.ctx) return;

    const count = 7 + Math.floor(Math.random() * 6);
    for (let i = 0; i < count; i++) {
      const delay = Math.random() * 600;
      setTimeout(() => {
        this.playSingleCrackle();
      }, delay);
    }
  }

  playSingleCrackle() {
    if (this.isMuted || !this.ctx) return;
    const t = this.ctx.currentTime;

    const osc = this.ctx.createOscillator();
    const gain = this.ctx.createGain();

    osc.type = 'triangle';
    osc.frequency.setValueAtTime(800 + Math.random() * 1400, t);

    gain.gain.setValueAtTime(0.12, t);
    gain.gain.exponentialRampToValueAtTime(0.001, t + 0.08);

    osc.connect(gain);
    gain.connect(this.ctx.destination);

    osc.start(t);
    osc.stop(t + 0.08);
  }
}

// Global singleton instance
window.soundEngine = new FireworksSoundEngine();

// Auto-initialize audio on first user touch / click
document.addEventListener('click', () => {
  window.soundEngine.init();
}, { once: true });
