/**
 * HTML5 Canvas Digital Signature Pad
 * 
 * Provides smooth, touch-friendly and stylus-responsive on-screen signing
 * for shopkeeper credit legal bindings.
 */

class SignaturePadManager {
    constructor(canvasElement) {
        this.canvas = canvasElement;
        this.ctx = canvasElement.getContext('2d');
        this.isDrawing = false;
        this.hasDrawn = false;
        this.lastX = 0;
        this.lastY = 0;

        this.init();
    }

    init() {
        this.resize();

        // Attach Mouse events
        this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
        this.canvas.addEventListener('mousemove', (e) => this.draw(e));
        window.addEventListener('mouseup', () => this.stopDrawing());

        // Attach Touch events for mobile screens
        this.canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            this.startDrawing(e.touches[0]);
        }, { passive: false });

        this.canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            this.draw(e.touches[0]);
        }, { passive: false });

        window.addEventListener('touchend', () => this.stopDrawing());
    }

    resize() {
        const rect = this.canvas.getBoundingClientRect();
        this.canvas.width = rect.width || 380;
        this.canvas.height = rect.height || 190;

        this.ctx.lineWidth = 2.8;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
        this.updateInkColor();
    }

    updateInkColor() {
        const isDark = document.documentElement.classList.contains('dark') || document.documentElement.getAttribute('data-theme') === 'dark';
        this.ctx.strokeStyle = isDark ? '#38bdf8' : '#0f172a'; // Vivid electric cyan on dark, deep navy on light
    }

    getCoordinates(e) {
        const rect = this.canvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }

    startDrawing(e) {
        this.isDrawing = true;
        this.updateInkColor();
        const coords = this.getCoordinates(e);
        this.lastX = coords.x;
        this.lastY = coords.y;
    }

    draw(e) {
        if (!this.isDrawing) return;
        const coords = this.getCoordinates(e);

        this.ctx.beginPath();
        this.ctx.moveTo(this.lastX, this.lastY);
        this.ctx.lineTo(coords.x, coords.y);
        this.ctx.stroke();

        this.lastX = coords.x;
        this.lastY = coords.y;
        this.hasDrawn = true;
    }

    stopDrawing() {
        this.isDrawing = false;
    }

    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.hasDrawn = false;
        this.updateInkColor();
    }

    isEmpty() {
        return !this.hasDrawn;
    }

    getImageDataUrl() {
        if (!this.hasDrawn) return null;
        return this.canvas.toDataURL('image/png');
    }
}

window.SignaturePadManager = SignaturePadManager;
