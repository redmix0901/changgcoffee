import './bootstrap';
import Alpine from 'alpinejs';
import { buildGradient, computeSpinRotation } from './spin-wheel';

window.Alpine = Alpine;

Alpine.data('spinWheel', (segments, spinUrl) => ({
    segments,
    spinUrl,
    rotation: 0,
    spinning: false,
    resultLabel: '',
    pendingResultLabel: '',
    errorMessage: '',
    wheelRadius: 140,
    showOverlay: false,
    _fwAnimId: null,
    init() {
        this.$nextTick(() => {
            const shell = this.$root.querySelector('.wheel-shell');
            if (shell) this.wheelRadius = shell.offsetWidth / 2;
            window.addEventListener('resize', () => {
                const s = this.$root.querySelector('.wheel-shell');
                if (s) this.wheelRadius = s.offsetWidth / 2;
            });
        });
    },
    launchFireworks() {
        this.$nextTick(() => {
            const canvas = this.$refs.celebCanvas;
            if (!canvas) return;
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            const ctx = canvas.getContext('2d');
            const colors = ['#f9a166','#ffd700','#ff4d6d','#4dff91','#4da6ff','#ff4dda','#ffffff','#ffe066'];
            const particles = [];

            const burst = (x, y, count = 70) => {
                for (let i = 0; i < count; i++) {
                    const angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.4;
                    const speed = Math.random() * 7 + 2;
                    particles.push({
                        x, y,
                        vx: Math.cos(angle) * speed,
                        vy: Math.sin(angle) * speed - 1,
                        alpha: 1,
                        color: colors[Math.floor(Math.random() * colors.length)],
                        size: Math.random() * 4 + 2,
                    });
                }
            };

            burst(canvas.width * 0.2, canvas.height * 0.3);
            burst(canvas.width * 0.8, canvas.height * 0.25);
            burst(canvas.width * 0.5, canvas.height * 0.2);

            let frame = 0;
            const animate = () => {
                if (!this.showOverlay) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    return;
                }
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                for (let i = particles.length - 1; i >= 0; i--) {
                    const p = particles[i];
                    p.x += p.vx;
                    p.y += p.vy;
                    p.vy += 0.12;
                    p.alpha -= 0.016;
                    if (p.alpha <= 0) { particles.splice(i, 1); continue; }
                    ctx.globalAlpha = p.alpha;
                    ctx.fillStyle = p.color;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                    ctx.fill();
                }
                ctx.globalAlpha = 1;
                frame++;
                if (frame % 55 === 0 && frame < 330) {
                    burst(Math.random() * canvas.width, Math.random() * canvas.height * 0.45, 50);
                }
                this._fwAnimId = requestAnimationFrame(animate);
            };
            if (this._fwAnimId) cancelAnimationFrame(this._fwAnimId);
            this._fwAnimId = requestAnimationFrame(animate);
        });
    },
    dismissOverlay() {
        this.showOverlay = false;
        if (this._fwAnimId) { cancelAnimationFrame(this._fwAnimId); this._fwAnimId = null; }
    },
    get wheelStyle() {
        return `background:${buildGradient(this.segments)};transform: rotate(${this.rotation}deg);`;
    },
    labelStyle(index) {
        const angle = 360 / Math.max(this.segments.length, 1);
        const rotate = angle * index + angle / 2;
        const offset = Math.round(this.wheelRadius * 0.63);
        const color = index % 2 === 0 ? '#f9f3df' : '#2d4b40';
        const shadow = index % 2 === 0 ? '0 1px 2px rgba(20, 31, 26, 0.28)' : 'none';

        return `transform: translate(-50%, -50%) rotate(${rotate}deg) translateY(-${offset}px);color:${color};text-shadow:${shadow};`;
    },
    async spin() {
        if (this.spinning || !this.segments.length) {
            return;
        }

        this.spinning = true;
        this.errorMessage = '';
        this.pendingResultLabel = '';
        this.resultLabel = '';

        try {
            const response = await fetch(this.spinUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({}),
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Spin failed.');
            }

            const winnerIndex = this.segments.findIndex((segment) => segment.id === payload.result.id);

            if (winnerIndex === -1) {
                throw new Error('Spin result does not match any wheel segment.');
            }

            this.rotation = computeSpinRotation(this.rotation, winnerIndex, this.segments.length);
            this.pendingResultLabel = payload.result.label;

            window.setTimeout(() => {
                this.resultLabel = this.pendingResultLabel;
                this.showOverlay = true;
                this.launchFireworks();
            }, 4300);
        } catch (error) {
            this.errorMessage = error.message;
        } finally {
            window.setTimeout(() => {
                this.spinning = false;
            }, 4700);
        }
    },
}));

Alpine.start();
