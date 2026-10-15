// Original Icey box animation from Secret and WhoIs; preserve its rendering and physics.
icey_canvas_home_hero();
function icey_canvas_home_hero() {

	var c = document.getElementById("icey-bg-canvas");
	var ctx = c.getContext("2d");
	if (!ctx) return;
	var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
	var frame = 0;

	function resize() {
		var canvasRect = c.getBoundingClientRect();
		c.width = canvasRect.width;
		c.height = canvasRect.height;
	}

	var light = {
		x: window.innerWidth / 2,
		y: window.innerHeight / 2
	}

	window.addEventListener('mousemove', function (event) {
		light.x = event.clientX;
		light.y = event.clientY;
	});

	window.addEventListener('touchmove', function (event) {
		if (event.touches.length > 0) {
			light.x = event.touches[0].clientX;
			light.y = event.touches[0].clientY;
		}
	}, { passive: true });

	var colors = ["#5fc1aa", "#E8355F", "#eabd46"];
	var maxBoxes = 20;
	if (window.innerWidth < 900) {
		maxBoxes = 10
	}

	var draggedBox = null;
	var lastMouseX = 0;
	var lastMouseY = 0;
	var mouseVx = 0;
	var mouseVy = 0;

	class Particle {
		constructor(x, y, radius, dx, dy, color) {
			this.x = x;
			this.y = y;
			this.radius = radius;
			this.dx = dx;
			this.dy = dy;
			this.alpha = 1;
			this.color = color;
		}
		draw() {
			ctx.save();
			ctx.globalAlpha = this.alpha;
			ctx.fillStyle = this.color;
			ctx.beginPath();
			ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2, false);
			ctx.fill();
			ctx.restore();
		}
		update() {
			this.draw();
			this.alpha -= 0.01;
			this.x += this.dx;
			this.y += this.dy;
		}
	}

	function drawLight() {
		ctx.beginPath();
		ctx.arc(light.x, light.y, 1000, 0, 2 * Math.PI);
		var gradient = ctx.createRadialGradient(light.x, light.y, 0, light.x, light.y, 1000);
		gradient.addColorStop(0, "#374a65");
		gradient.addColorStop(1, "#132033");
		ctx.fillStyle = gradient;
		ctx.fill();
	}

	function Box() {
		this.box_size = Math.floor((Math.random() * 30) + 30);
		this.x = Math.floor((Math.random() * c.width) + 1);
		this.y = Math.floor((Math.random() * c.height) + 1);
		this.r = Math.random() * Math.PI;
		this.shadow_length = 2000;
		this.color = colors[Math.floor((Math.random() * colors.length))];
		this.set_to_explode = 0;
		this.set_to_kill = 0;
		this.alpha = 1;
		var speed = (60 - this.box_size) / 40;
		this.vx = (Math.random() < 0.5 ? -1 : 1) * speed;
		this.vy = (Math.random() < 0.5 ? -1 : 1) * speed;
		this.mass = this.box_size;
		this.offsetX = 0;
		this.offsetY = 0;


		this.getDots = function () {

			var full = (Math.PI * 2) / 4;

			var p1 = {
				x: this.x + this.box_size * Math.sin(this.r),
				y: this.y + this.box_size * Math.cos(this.r)
			};
			var p2 = {
				x: this.x + this.box_size * Math.sin(this.r + full),
				y: this.y + this.box_size * Math.cos(this.r + full)
			};
			var p3 = {
				x: this.x + this.box_size * Math.sin(this.r + full * 2),
				y: this.y + this.box_size * Math.cos(this.r + full * 2)
			};
			var p4 = {
				x: this.x + this.box_size * Math.sin(this.r + full * 3),
				y: this.y + this.box_size * Math.cos(this.r + full * 3)
			};

			return {
				p1: p1,
				p2: p2,
				p3: p3,
				p4: p4
			};
		}
		this.move_box = function () {
			var speed = (60 - this.box_size) / 40;
			this.r += speed * 0.022;
			this.x += this.vx;
			this.y += this.vy;
		}
		this.draw = function () {
			var dots = this.getDots();
			ctx.beginPath();
			ctx.moveTo(dots.p1.x, dots.p1.y);
			ctx.lineTo(dots.p2.x, dots.p2.y);
			ctx.lineTo(dots.p3.x, dots.p3.y);
			ctx.lineTo(dots.p4.x, dots.p4.y);
			ctx.globalAlpha = this.alpha;
			ctx.fillStyle = this.color;
			ctx.fill();

			if (this.y - this.box_size > c.height) {
				this.y -= c.height + 100;
			}
			if (this.x - this.box_size > c.width) {
				this.x -= c.width + 100;
			}
		}
		this.drawShadow = function () {
			var dots = this.getDots();
			var angles = [];
			var points = [];

			for (var dot in dots) {
				var angle = Math.atan2(light.y - dots[dot].y, light.x - dots[dot].x);
				var endX = dots[dot].x + this.shadow_length * Math.sin(-angle - Math.PI / 2);
				var endY = dots[dot].y + this.shadow_length * Math.cos(-angle - Math.PI / 2);
				angles.push(angle);
				points.push({
					endX: endX,
					endY: endY,
					startX: dots[dot].x,
					startY: dots[dot].y
				});
			};

			for (var i = points.length - 1; i >= 0; i--) {
				var n = i == 3 ? 0 : i + 1;
				ctx.beginPath();
				ctx.moveTo(points[i].startX, points[i].startY);
				ctx.lineTo(points[n].startX, points[n].startY);
				ctx.lineTo(points[n].endX, points[n].endY);
				ctx.lineTo(points[i].endX, points[i].endY);
				ctx.fillStyle = "#13203333";
				ctx.fill();
			};
		}


		this.particles = [];
		this.explode = function () {

			if (this.particles.length < 100) {
				for (var i = 0; i <= 30; i++) {
					let dx = (Math.random() - 0.5) * (Math.random() * 6);
					let dy = (Math.random() - 0.5) * (Math.random() * 6);
					let radius = Math.random() * 3;
					let particle = new Particle(this.x, this.y, radius, dx, dy, this.color);

					this.particles.push(particle);

					this.set_to_explode = 1;
				}
			}
		}

		this.do_explosion = function () {
			this.particles.forEach((particle, i) => {
				if (particle.alpha <= 0) {
					this.particles.splice(i, 1);
				} else particle.update()
			})
		}


	}

	var boxes = [];

	function draw() {
		ctx.clearRect(0, 0, c.width, c.height);
		drawLight();

		for (var i = 0; i < boxes.length; i++) {
			if (!motion.matches && boxes[i] !== draggedBox) {
				boxes[i].move_box();
			}
			boxes[i].drawShadow();
		};
		for (var i = 0; i < boxes.length; i++) {
			if (!motion.matches) collisionDetection(i)

			if (!motion.matches && boxes[i].set_to_explode === 1) {
				boxes[i].do_explosion();
			}
			if (!motion.matches && boxes[i].set_to_kill === 1) {
				boxes[i].alpha -= 0.01;
				if (boxes[i].alpha <= 0) {
					boxes.splice(i, 1);
					boxes.push(new Box());
				}
			}

			boxes[i].draw();

		};

		if (!motion.matches && !document.hidden) frame = window.requestAnimationFrame(draw);
	}

	resize();

	while (boxes.length < maxBoxes) {
		boxes.push(new Box());
	}

	// Keep the service's existing reduced-motion and hidden-tab behavior.
	function restart() {
		window.cancelAnimationFrame(frame);
		draggedBox = null;
		draw();
	}
	window.addEventListener('resize', function () { resize(); restart(); });
	motion.addEventListener('change', restart);
	document.addEventListener('visibilitychange', restart);
	draw();

	function collisionDetection(b) {
		var box1 = boxes[b];

		if (box1 === draggedBox) return;

		if (box1.x - box1.box_size < 0 || box1.x + box1.box_size > c.width) {
			box1.vx *= -1;
			box1.explode();
		}
		if (box1.y - box1.box_size < 0 || box1.y + box1.box_size > c.height) {
			box1.vy *= -1;
			box1.explode();
		}

		for (var i = b + 1; i < boxes.length; i++) {
			var box2 = boxes[i];
			if (box2 === draggedBox) continue;

			var dx = box1.x - box2.x;
			var dy = box1.y - box2.y;
			var d = Math.sqrt(dx * dx + dy * dy);

			if (d < box1.box_size + box2.box_size) {

				var normalX = dx / d;
				var normalY = dy / d;
				var tangentX = -normalY;
				var tangentY = normalX;

				var dpTan1 = box1.vx * tangentX + box1.vy * tangentY;
				var dpTan2 = box2.vx * tangentX + box2.vy * tangentY;

				var dpNorm1 = box1.vx * normalX + box1.vy * normalY;
				var dpNorm2 = box2.vx * normalX + box2.vy * normalY;

				var m1 = (dpNorm1 * (box1.mass - box2.mass) + 2 * box2.mass * dpNorm2) / (box1.mass + box2.mass);
				var m2 = (dpNorm2 * (box2.mass - box1.mass) + 2 * box1.mass * dpNorm1) / (box1.mass + box2.mass);

				box1.vx = tangentX * dpTan1 + normalX * m1;
				box1.vy = tangentY * dpTan1 + normalY * m1;
				box2.vx = tangentX * dpTan2 + normalX * m2;
				box2.vy = tangentY * dpTan2 + normalY * m2;


				box1.explode();
				box2.explode();

				box1.box_size = box1.box_size > 5 ? box1.box_size -= 5 : 5;
				box2.box_size = box2.box_size > 5 ? box2.box_size -= 5 : 5;

				if (box1.box_size <= 10) {
					box1.set_to_kill = 1;
				}

				if (box2.box_size <= 10) {
					box2.set_to_kill = 1;
				}
			}
		}
	}

	c.addEventListener('mousedown', function (event) {
		if (motion.matches) return;
		var rect = c.getBoundingClientRect();
		var x = event.clientX - rect.left;
		var y = event.clientY - rect.top;

		for (var i = boxes.length - 1; i >= 0; i--) {
			var box = boxes[i];
			if (y > box.y - box.box_size && y < box.y + box.box_size && x > box.x - box.box_size && x < box.x + box.box_size) {
				draggedBox = box;
				draggedBox.offsetX = x - draggedBox.x;
				draggedBox.offsetY = y - draggedBox.y;
				draggedBox.vx = 0;
				draggedBox.vy = 0;
				break;
			}
		}
	});

	c.addEventListener('mousemove', function (event) {
		var rect = c.getBoundingClientRect();
		var x = event.clientX - rect.left;
		var y = event.clientY - rect.top;

		mouseVx = x - lastMouseX;
		mouseVy = y - lastMouseY;
		lastMouseX = x;
		lastMouseY = y;

		if (draggedBox) {
			draggedBox.x = x - draggedBox.offsetX;
			draggedBox.y = y - draggedBox.offsetY;
			draggedBox.vx = mouseVx;
			draggedBox.vy = mouseVy;
		}
	});

	c.addEventListener('mouseup', function (event) {
		if (draggedBox) {
			var maxThrowSpeed = 30;
			var speed = Math.sqrt(draggedBox.vx * draggedBox.vx + draggedBox.vy * draggedBox.vy);
			if (speed > maxThrowSpeed) {
				draggedBox.vx = (draggedBox.vx / speed) * maxThrowSpeed;
				draggedBox.vy = (draggedBox.vy / speed) * maxThrowSpeed;
			}
			draggedBox = null;
		}
	});

	c.addEventListener('touchstart', function (event) {
		if (motion.matches) return;
		event.preventDefault();
		var rect = c.getBoundingClientRect();
		var touch = event.touches[0];
		var x = touch.clientX - rect.left;
		var y = touch.clientY - rect.top;

		for (var i = boxes.length - 1; i >= 0; i--) {
			var box = boxes[i];
			if (y > box.y - box.box_size && y < box.y + box.box_size && x > box.x - box.box_size && x < box.x + box.box_size) {
				draggedBox = box;
				draggedBox.offsetX = x - draggedBox.x;
				draggedBox.offsetY = y - draggedBox.y;
				draggedBox.vx = 0;
				draggedBox.vy = 0;
				break;
			}
		}
	}, { passive: false });

	c.addEventListener('touchmove', function (event) {
		if (motion.matches) return;
		event.preventDefault();
		var rect = c.getBoundingClientRect();
		var touch = event.touches[0];
		var x = touch.clientX - rect.left;
		var y = touch.clientY - rect.top;

		mouseVx = x - lastMouseX;
		mouseVy = y - lastMouseY;
		lastMouseX = x;
		lastMouseY = y;

		if (draggedBox) {
			draggedBox.x = x - draggedBox.offsetX;
			draggedBox.y = y - draggedBox.offsetY;
			draggedBox.vx = mouseVx;
			draggedBox.vy = mouseVy;
		}
	}, { passive: false });

	c.addEventListener('touchend', function (event) {
		if (motion.matches) return;
		event.preventDefault();
		if (draggedBox) {
			var maxThrowSpeed = 30;
			var speed = Math.sqrt(draggedBox.vx * draggedBox.vx + draggedBox.vy * draggedBox.vy);
			if (speed > maxThrowSpeed) {
				draggedBox.vx = (draggedBox.vx / speed) * maxThrowSpeed;
				draggedBox.vy = (draggedBox.vy / speed) * maxThrowSpeed;
			}
			draggedBox = null;
		}
	});
}
