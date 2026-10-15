'use strict';

// Native interpretation of the moving colored boxes used by the Icey reference sites.
(() => {
	const canvas = document.querySelector('#icey-bg-canvas');
	const context = canvas?.getContext('2d');
	if (!context) return;
	const motion = matchMedia('(prefers-reduced-motion: reduce)');
	const colors = ['#5fc1aa', '#e8355f', '#eabd46'];
	let width = innerWidth;
	let height = innerHeight;
	let frame = 0;
	let previous = 0;
	let dragged = null;
	const light = { x: width / 2, y: height / 2 };
	const boxes = Array.from({ length: width < 900 ? 10 : 20 }, () => ({
		x: Math.random() * width, y: Math.random() * height,
		size: 42 + Math.random() * 42, angle: Math.random() * Math.PI,
		vx: (Math.random() - .5) * 45, vy: (Math.random() - .5) * 45,
		color: colors[Math.floor(Math.random() * colors.length)],
	}));

	function resize() {
		width = innerWidth;
		height = innerHeight;
		const ratio = Math.min(devicePixelRatio || 1, 2);
		canvas.width = Math.round(width * ratio);
		canvas.height = Math.round(height * ratio);
		context.setTransform(ratio, 0, 0, ratio, 0, 0);
		for (const box of boxes) {
			box.x = Math.min(Math.max(box.size, box.x), width - box.size);
			box.y = Math.min(Math.max(box.size, box.y), height - box.size);
		}
		if (motion.matches) draw(0);
	}

	function draw(time) {
		const delta = previous && time ? Math.min((time - previous) / 1000, .05) : 0;
		previous = time;
		context.clearRect(0, 0, width, height);
		const gradient = context.createRadialGradient(light.x, light.y, 0, light.x, light.y, 1000);
		gradient.addColorStop(0, '#374a65');
		gradient.addColorStop(1, '#132033');
		context.fillStyle = gradient;
		context.fillRect(0, 0, width, height);
		for (const box of boxes) {
			if (!motion.matches && box !== dragged) {
				box.x += box.vx * delta;
				box.y += box.vy * delta;
				box.angle += delta * .08;
				if (box.x < box.size || box.x > width - box.size) box.vx *= -1;
				if (box.y < box.size || box.y > height - box.size) box.vy *= -1;
				box.x = Math.min(Math.max(box.size, box.x), width - box.size);
				box.y = Math.min(Math.max(box.size, box.y), height - box.size);
			}
			const corners = [[-1, -1], [1, -1], [1, 1], [-1, 1]].map(([x, y]) => ({
				x: box.x + (x * Math.cos(box.angle) - y * Math.sin(box.angle)) * box.size / 2,
				y: box.y + (x * Math.sin(box.angle) + y * Math.cos(box.angle)) * box.size / 2,
			}));
			const ends = corners.map((corner) => {
				const angle = Math.atan2(corner.y - light.y, corner.x - light.x);
				return { x: corner.x + Math.cos(angle) * 2000, y: corner.y + Math.sin(angle) * 2000 };
			});
			context.fillStyle = '#13203333';
			for (let index = 0; index < 4; index++) {
				const next = (index + 1) % 4;
				context.beginPath();
				context.moveTo(corners[index].x, corners[index].y);
				context.lineTo(corners[next].x, corners[next].y);
				context.lineTo(ends[next].x, ends[next].y);
				context.lineTo(ends[index].x, ends[index].y);
				context.fill();
			}
			context.save();
			context.translate(box.x, box.y);
			context.rotate(box.angle);
			context.fillStyle = box.color;
			context.fillRect(-box.size / 2, -box.size / 2, box.size, box.size);
			context.restore();
		}
	}

	function animate(time) {
		draw(time);
		frame = requestAnimationFrame(animate);
	}
	function restart() {
		cancelAnimationFrame(frame);
		previous = 0;
		draw(0);
		if (!motion.matches && !document.hidden) frame = requestAnimationFrame(animate);
	}
	addEventListener('resize', resize);
	addEventListener('pointermove', (event) => {
		light.x = event.clientX;
		light.y = event.clientY;
		if (dragged) { dragged.x = event.clientX; dragged.y = event.clientY; }
	});
	canvas.addEventListener('pointerdown', (event) => {
		if (motion.matches || event.pointerType === 'touch') return;
		dragged = boxes.findLast((box) => Math.abs(event.clientX - box.x) < box.size && Math.abs(event.clientY - box.y) < box.size) ?? null;
		if (dragged) canvas.setPointerCapture(event.pointerId);
	});
	canvas.addEventListener('pointerup', () => { dragged = null; });
	canvas.addEventListener('pointercancel', () => { dragged = null; });
	motion.addEventListener('change', restart);
	document.addEventListener('visibilitychange', restart);
	resize();
	restart();
})();
