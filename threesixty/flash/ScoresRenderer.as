class ScoresRenderer {
	
	static var COLOURS_BY_FILTER = [];
	
	static var TWO_PI:Number = 2.0 * Math.PI;
	static var HALF_PI:Number = 0.5 * Math.PI;
	static var MINOR_RADIUS:Number = 80;
	static var RADIUS_DELTA:Number = 35;
	static var BASE_RADIUS:Number = MINOR_RADIUS + RADIUS_DELTA;
	static var MIN_SKILL_SCORE:Number = 0;
	static var MAX_SKILL_SCORE:Number = 4;
	static var KEY_CONTAINER_X_COORD:Number = -50;
	static var GAP_BETWEEN_LABELS:Number = 6;
	static var DRAW_MINOR_RADIUS:Boolean = false;
	static var MAX_KEY_ENTRIES:Number = 8;
	static var ZOOM_IN_PERIOD:Number = 500;
	
	var timeline:MovieClip;
	var canvasGrid:MovieClip;
	var canvasScores:MovieClip;
	var key:MovieClip;
	
	var scores:Array;
	var filterz:Array;
	var angles:Array;
	var cumulativeAngles:Array;
	var radiansPerCompetency:Number;
	var mouseP:Point;
	var currentSkill:Skill;
	var t0:Number;
	
	function ScoresRenderer(timeline:MovieClip, scores:Array, filterz:Array) {
		this.timeline = timeline;
		this.canvasGrid = timeline.canvas_mc.canvas2_mc;
		this.canvasScores = timeline.canvas_mc.canvas1_mc;
		this.key = timeline.canvas_mc.key_mc;
		this.scores = scores;
		this.filterz = filterz;
		this.angles = [];
		this.cumulativeAngles = [];
		this.mouseP = new Point();
		this.currentSkill = null;
	}
	
	function renderAll():Void {
		// initialize tween values
		timeline.canvas_mc._xscale = timeline.canvas_mc._yscale = timeline.canvas_mc._alpha = 0;
		
		// render out the grid
		renderCompetencyGrid();
		renderSkillGrid();
		
		// render out the scores and the key
		var keyContainer:MovieClip = key.createEmptyMovieClip("keyContainer_mc", key.getNextHighestDepth());
		var y:Number = 0;
		var colour:Number;
		var keyEntryCount:Number = 0;
		
		// do each filter
		for (var i:Number = 0; i < filterz.length; ++i) {
			colour = COLOURS_BY_FILTER[filterz[i][0]];
			
			// do the line
			canvasScores.lineStyle(3, colour);
			renderOneScore(scores[filterz[i][0]]);
			
			// do the key entry
			if (keyEntryCount < MAX_KEY_ENTRIES) {
				keyContainer.attachMovie("KeyItem", "key" + i.toString() + "_mc", keyContainer.getNextHighestDepth());
				var k:MovieClip = keyContainer["key" + i.toString() + "_mc"];
				k._y = y;
				k.label_txt.text = filterz[i][1];
				y += k._height + GAP_BETWEEN_LABELS;
				++keyEntryCount;
			}
			
			// draw the key box
			k.beginFill(colour);
			k.moveTo(0, 0);
			k.lineTo(20, 0);
			k.lineTo(20, 20);
			k.lineTo(0, 20);
			k.endFill();
		}
		
		// adjust the position of the key container
		keyContainer._x = KEY_CONTAINER_X_COORD;
		keyContainer._y = -0.5 * keyContainer._height;
		
		// do initial tween
		t0 = getTimer();
		timeline.canvas_mc.onEnterFrame = EventDelegate.create(this, onEnterFrame);
	}
	
	function onEnterFrame():Void {
		var elapsed:Number = getTimer() - t0;
		var f:Number = Math.min(elapsed / ZOOM_IN_PERIOD, 1.0);
		if (f >= 1) {
			delete timeline.canvas_mc.onEnterFrame;
			timeline.canvas_mc._xscale = timeline.canvas_mc._yscale = timeline.canvas_mc._alpha = 100;
		} else {
			timeline.canvas_mc._xscale = timeline.canvas_mc._yscale = timeline.canvas_mc._alpha = easeInOut(elapsed, 0, 100, ZOOM_IN_PERIOD);
		}
		updateAfterEvent();
	}
	
	function enableRollover():Void {
		Mouse.addListener(this);
	}
	
	function disableRollover():Void {
		Mouse.removeListener(this);
	}
	
	function onMouseMove():Void {
		mouseP.x = timeline.canvas_mc._xmouse;
		mouseP.y = timeline.canvas_mc._ymouse;
		
		// pythagoras
		var r:Number = Math.sqrt(mouseP.x * mouseP.x + mouseP.y * mouseP.y);
		
		// ignore if not in range
		if (r < BASE_RADIUS || r > BASE_RADIUS + MAX_SKILL_SCORE * RADIUS_DELTA) {
			if (currentSkill != null) {
				currentSkill = null;
				displayCurrentSkill();
			}
		} else {
			// get angle from mouse coordinates
			var angle:Number = Math.atan2(mouseP.y, mouseP.x) + HALF_PI;
			if (angle < 0) {
				angle = TWO_PI - Math.abs(angle);
			}
			
			// find skill from angle
			var i:Number = 0;
			while (angle > cumulativeAngles[i]) {
				++i;
			}
			var skill:Skill = Skill.allSkills[i];
			if (currentSkill == null || (currentSkill != skill)) {
				currentSkill = skill;
				displayCurrentSkill(angles[i]);
			}
		}
	}
	
	function displayCurrentSkill(angle:Number):Void {
		// setup the competency and skill texts in the centre of the circles
		var compText:TextField = timeline.canvas_mc.comp_txt;
		var skillText:TextField = timeline.canvas_mc.skill_txt;
		compText.selectable = false;
		skillText.selectable = false;
		compText.text = currentSkill == null ? "" : currentSkill.competency.name;
		skillText.text = currentSkill == null ? "" : currentSkill.name;
		
		// determine whether to show the key
		key._visible = currentSkill == null;
		
		// determine whether to show the line that points to/through the skill
		var line:MovieClip = timeline.canvas_mc.line_mc;
		line.clear();
		if (currentSkill != null) {
			line.lineStyle(3, 0x000000);
			var p:Point = new Point();
			
			var r:Number = BASE_RADIUS - 10;
			p.x = r * Math.cos(angle - HALF_PI);
			p.y = r * Math.sin(angle - HALF_PI);
			line.moveTo(p.x, p.y);
			
			r = BASE_RADIUS + MAX_SKILL_SCORE * RADIUS_DELTA + 10;
			p.x = r * Math.cos(angle - HALF_PI);
			p.y = r * Math.sin(angle - HALF_PI);
			line.lineTo(p.x, p.y);
		}
	}
	
	function renderCompetencyGrid():Void {
		var competencyCount:Number = Competency.allCompetencies.length;
		radiansPerCompetency = TWO_PI / competencyCount;
		
		var p1:Point = new Point();
		var p2:Point = new Point();
		var r1:Number;
		var r2:Number;
		var angle:Number = 0;
		var score:Number;
		var scoreInstanceIndex:Number = 0;
		
		for (var i:Number = 0; i < competencyCount; ++i) {
			// thick line
			r1 = BASE_RADIUS;
			r2 = BASE_RADIUS + MAX_SKILL_SCORE * RADIUS_DELTA;
			p1.x = r1 * Math.cos(angle - HALF_PI);
			p1.y = r1 * Math.sin(angle - HALF_PI);
			p2.x = r2 * Math.cos(angle - HALF_PI);
			p2.y = r2 * Math.sin(angle - HALF_PI);
			canvasGrid.lineStyle(3, 0x000000);
			canvasGrid.moveTo(p1.x, p1.y);
			canvasGrid.lineTo(p2.x, p2.y);
			
			// scores along the thick line
			for (score = MIN_SKILL_SCORE + 1; score <= MAX_SKILL_SCORE; ++score) {
				timeline.canvas_mc.attachMovie("Score", "score" + scoreInstanceIndex.toString(), timeline.canvas_mc.getNextHighestDepth());
				r1 = BASE_RADIUS + score * RADIUS_DELTA;
				p1.x = r1 * Math.cos(angle - HALF_PI);
				p1.y = r1 * Math.sin(angle - HALF_PI);
				timeline.canvas_mc["score" + scoreInstanceIndex.toString()].score_txt.text = score.toString();
				timeline.canvas_mc["score" + scoreInstanceIndex.toString()]._x = p1.x;
				timeline.canvas_mc["score" + scoreInstanceIndex.toString()]._y = p1.y;
				++scoreInstanceIndex;
			}
			
			// thin line
			if (DRAW_MINOR_RADIUS) {
				r1 = MINOR_RADIUS;
				r2 = BASE_RADIUS;
				p1.x = r1 * Math.cos(angle - HALF_PI);
				p1.y = r1 * Math.sin(angle - HALF_PI);
				p2.x = r2 * Math.cos(angle - HALF_PI);
				p2.y = r2 * Math.sin(angle - HALF_PI);
				canvasGrid.lineStyle(0, 0x000000);
				canvasGrid.moveTo(p1.x, p1.y);
				canvasGrid.lineTo(p2.x, p2.y);
			}
			
			// change angle
			angle += radiansPerCompetency;
		}
		
		// thin inner circle
		if (DRAW_MINOR_RADIUS) {
			canvasGrid.lineStyle(0, 0x000000);
			drawCircle(0, 0, MINOR_RADIUS);
		}
		
		// thick base circle
		canvasGrid.lineStyle(3, 0x000000);
		drawCircle(0, 0, BASE_RADIUS);
		
		// thin concentric circles per score rating
		canvasGrid.lineStyle(0, 0x000000, 50);
		for (score = MIN_SKILL_SCORE + 1; score <= MAX_SKILL_SCORE; ++score) {
			drawCircle(0, 0, BASE_RADIUS + score * RADIUS_DELTA);
		}
	}
	
	function renderSkillGrid():Void {
		var competencyCount:Number = Competency.allCompetencies.length;
		var radiansPerCompetency:Number = TWO_PI / competencyCount;
		
		var p1:Point = new Point();
		var p2:Point = new Point();
		var r1:Number;
		var r2:Number;
		var angle:Number = 0;
		
		for (var i:Number = 0; i < competencyCount; ++i) {
			var skillCount:Number = Competency.allCompetencies[i].skillCount;
			var radiansPerSkill:Number = radiansPerCompetency / skillCount;
			
			for (var j:Number = 0; j < skillCount; ++j) {
				// thin line
				r1 = BASE_RADIUS;
				r2 = BASE_RADIUS + MAX_SKILL_SCORE * RADIUS_DELTA;
				p1.x = r1 * Math.cos(angle - HALF_PI);
				p1.y = r1 * Math.sin(angle - HALF_PI);
				p2.x = r2 * Math.cos(angle - HALF_PI);
				p2.y = r2 * Math.sin(angle - HALF_PI);
				canvasGrid.lineStyle(0, 0x000000);
				canvasGrid.moveTo(p1.x, p1.y);
				canvasGrid.lineTo(p2.x, p2.y);
				
				// change angle
				angle += radiansPerSkill;
				cumulativeAngles.push(angle);
				angles.push(angle - 0.5 * radiansPerSkill);
			}
		}
	}
	
	function renderOneScore(score:Array):Void {
		var p:Point = new Point();
		
		// move to first point
		var r:Number = BASE_RADIUS + score[0].score * RADIUS_DELTA;
		p.x = r * Math.cos(angles[0] - HALF_PI);
		p.y = r * Math.sin(angles[0] - HALF_PI);
		canvasScores.moveTo(p.x, p.y);
		
		// draw a line for each score
		for (var i:Number = 0; i < score.length; ++i) {
			r = BASE_RADIUS + score[i].score * RADIUS_DELTA;
			p.x = r * Math.cos(angles[i] - HALF_PI);
			p.y = r * Math.sin(angles[i] - HALF_PI);
			canvasScores.lineTo(p.x, p.y);
		}
		
		// draw line between last skill in last competency and first skill in first competency
		r = BASE_RADIUS + score[0].score * RADIUS_DELTA;
		p.x = r * Math.cos(angles[0] - HALF_PI);
		p.y = r * Math.sin(angles[0] - HALF_PI);
		canvasScores.lineTo(p.x, p.y);
	}
	
	function drawCircle(centerX:Number, centerY:Number, radius:Number, sides:Number) {
		sides = sides | 100;
		canvasGrid.moveTo(centerX + radius,  centerY);
		for (var i:Number = 0; i <= sides; ++i){
			var pointRatio:Number = i / sides;
			var xSteps:Number = Math.cos(pointRatio * TWO_PI);
			var ySteps:Number = Math.sin(pointRatio * TWO_PI);
			var pointX:Number = centerX + xSteps * radius;
			var pointY:Number = centerY + ySteps * radius;
			canvasGrid.lineTo(pointX, pointY);
		}
	}
	
	static function easeInOut(t:Number, b:Number, c:Number, d:Number, s:Number):Number {
		if (s == undefined) s = 1.70158; 
		if ((t /= d / 2) < 1) return c / 2 * (t * t * (((s *= 1.525) + 1) * t - s)) + b;
		return c / 2 * ((t -= 2) * t * (((s *= 1.525) + 1) * t + s) + 2) + b;
	}
	
}
